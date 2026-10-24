<?php

namespace Tests\Feature;

use App\Enums\CircularStatus;
use App\Enums\DecisionKind;
use App\Enums\MeetingStatus;
use App\Enums\MinuteStatus;
use App\Enums\TaskStatus;
use App\Models\AgendaItem;
use App\Models\AuditLog;
use App\Models\CircularResolution;
use App\Models\Company;
use App\Models\Document;
use App\Models\Meeting;
use App\Models\Minute;
use App\Models\Task;
use App\Services\Ai\AiAssistant;
use App\Services\Ai\Retriever;
use App\Services\CircularService;
use App\Services\DecisionService;
use App\Services\FinancialAnalysis;
use App\Services\MinuteService;
use App\Services\QuorumService;
use App\Services\TaskService;
use App\Support\Access;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class ProcessRulesTest extends TestCase
{
    public function test_quorum_uses_actual_attendance_not_rsvp(): void
    {
        $m = Meeting::where('status', MeetingStatus::GagalKuorum->value)->firstOrFail();
        $rsvp = $m->participants()->where('has_vote', true)->where('rsvp', 'hadir')->count();
        $q = app(QuorumService::class)->evaluate($m)[0];

        $this->assertFalse($q['met']);
        $this->assertGreaterThanOrEqual($q['required'], $rsvp, 'RSVP cukup, tetapi tidak dipakai sebagai bukti kuorum');
    }

    public function test_recused_member_cannot_vote_and_material_is_closed(): void
    {
        $item = AgendaItem::whereHas('conflicts')->firstOrFail();
        $conflicted = $item->conflicts()->first()->user;

        $this->assertFalse(Access::canSeeAgendaItem($conflicted, $item));
        $this->assertNotContains($conflicted->id, app(DecisionService::class)->eligibleVoters($item, app(DecisionService::class)->organFor($item, DecisionKind::NasihatDekom))->pluck('id'));
    }

    public function test_joint_meeting_votes_stay_within_each_organ(): void
    {
        $item = AgendaItem::whereHas('meeting', fn ($q) => $q->where('scheduled_at', '>=', '2026-08-25')->where('scheduled_at', '<', '2026-08-26'))->orderBy('seq')->firstOrFail();
        $item->meeting->participants()->update(['attended' => true]);
        $director = $this->demo('dirut');

        $this->expectException(InvalidArgumentException::class);
        app(DecisionService::class)->record($item, DecisionKind::PersetujuanDekom, ['text' => 'Uji'], [$director->id => 'setuju']);
    }

    public function test_final_minutes_are_never_overwritten(): void
    {
        $final = Minute::where('status', MinuteStatus::Final->value)->where('kind', 'risalah')->firstOrFail();
        $body = $final->body;

        try {
            $final->update(['body' => 'diubah']);
            $this->fail('Risalah final tidak boleh diubah');
        } catch (RuntimeException) {
        }

        $addendum = app(MinuteService::class)->correct($final->fresh(), 'Salah ketik angka', 'Koreksi angka', $this->demo('sekdekom'));
        $this->assertSame($final->id, $addendum->corrects_id);
        $this->assertSame('addendum', $addendum->kind);
        $this->assertSame($body, $final->fresh()->body);
    }

    public function test_task_closes_only_after_evidence_is_verified_by_someone_else(): void
    {
        $svc = app(TaskService::class);
        $task = Task::where('status', TaskStatus::Berjalan->value)->whereNotNull('verifier_id')->firstOrFail();
        $pic = $task->pic;

        $evidence = $svc->submit($task, 'Bukti uji', null, null, $pic);
        $this->assertSame(TaskStatus::Diajukan, $task->fresh()->status);

        try {
            $svc->verify($evidence, true, null, $pic);
            $this->fail('PIC tidak boleh memverifikasi bukti sendiri');
        } catch (RuntimeException) {
        }

        try {
            $svc->verify($evidence, false, '', $task->verifier);
            $this->fail('Penolakan wajib beralasan');
        } catch (RuntimeException) {
        }

        $svc->verify($evidence, false, 'Belum ada tanda tangan', $task->verifier);
        $this->assertSame(TaskStatus::Berjalan, $task->fresh()->status);
        $this->assertSame($task->rejection_count + 1, $task->fresh()->rejection_count);

        $second = $svc->submit($task->fresh(), 'Bukti lengkap', null, null, $pic);
        $svc->verify($second, true, null, $task->verifier);
        $this->assertSame(TaskStatus::Selesai, $task->fresh()->status);

        $svc->reopen($task->fresh(), 'Temuan berulang', $task->verifier);
        $this->assertSame(TaskStatus::DibukaKembali, $task->fresh()->status);
    }

    public function test_circular_needs_all_members_before_deadline(): void
    {
        $this->assertSame(CircularStatus::Kedaluwarsa, CircularResolution::where('number', 'SRK-DIR/03/2026')->first()->status);
        $this->assertSame(CircularStatus::Ditolak, CircularResolution::where('number', 'SRK-DIR/02/2026')->first()->status);

        $open = CircularResolution::where('status', CircularStatus::Beredar->value)->firstOrFail();
        $pending = $open->responses()->where('response', 'menunggu')->first();
        app(CircularService::class)->respond($open, $pending->user, false, 'Tidak setuju');
        $this->assertSame(CircularStatus::Ditolak, $open->fresh()->status);
        $this->assertNull($open->fresh()->decision);
    }

    public function test_ratios_are_computed_by_the_application(): void
    {
        $company = Company::whereNull('parent_id')->first();
        $fin = app(FinancialAnalysis::class);
        $growth = $fin->growth($company);
        $ratios = collect($fin->ratios($company))->keyBy('code');

        $this->assertEqualsWithDelta(-7.20, $growth['penjualan']['pct'], 0.01);
        $this->assertEqualsWithDelta(-63.30, $growth['laba_bersih']['pct'], 0.01);
        $this->assertEqualsWithDelta(0.5637, $ratios['rasio_lancar']['values']['2025'], 0.0001);
        $this->assertNotEmpty($fin->qualityFlags($company), 'Angka 56,37 x di hlm. 19 ditandai perlu klarifikasi');
    }

    public function test_ai_without_evidence_does_not_call_the_model(): void
    {
        config(['mufakat.ai.api_key' => 'sk-test']);
        Http::fake();

        $result = app(AiAssistant::class)->run($this->demo('komut'), 'tanya_dokumen', ['question' => 'Berapa jumlah kuoltrapex vandermiro zxqv?']);

        $this->assertSame('bukti_tidak_cukup', $result->status);
        $this->assertNotEmpty($result->output['missing_information']);
        Http::assertNothingSent();
    }

    public function test_ai_drops_citations_outside_allowed_context(): void
    {
        config(['mufakat.ai.api_key' => 'sk-test']);
        $doc = Document::where('kind', 'laporan_tahunan')->first();
        Http::fake(['*' => Http::response(['model' => 'gpt-test', 'usage' => ['input_tokens' => 10, 'output_tokens' => 5], 'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => json_encode([
            'summary' => 'Uji', 'questions' => [], 'items' => [], 'missing_information' => [], 'draft_text' => '',
            'statements' => [
                ['text' => 'Penjualan neto 2025 Rp9.223.328 juta', 'type' => 'fakta', 'sources' => [['document_id' => $doc->id, 'page' => 16]]],
                ['text' => 'Angka karangan', 'type' => 'fakta', 'sources' => [['document_id' => 99999, 'page' => 1]]],
            ],
        ])]]]]])]);

        $result = app(AiAssistant::class)->run($this->demo('komut'), 'tanya_dokumen', ['question' => 'Berapa penjualan neto 2025 dan rugi tahun berjalan?', 'document_id' => $doc->id]);

        $this->assertSame('berhasil', $result->status);
        $this->assertFalse($result->output['statements'][0]['unverified']);
        $this->assertTrue($result->output['statements'][1]['unverified']);
        $this->assertNotEmpty($result->output['warnings']);
    }

    public function test_directors_cannot_retrieve_remuneration_material(): void
    {
        $doc = Document::where('title', 'like', '%Remunerasi%')->firstOrFail();
        $director = $this->demo('dirkom');

        $this->assertFalse(Access::canReadDocument($director, $doc));
        $this->assertNotContains($doc->id, app(Retriever::class)->allowedDocumentIds($director)->all());

        $this->actingAs($director)->get('/app/ruang-baca?dokumen='.$doc->id);
        $this->assertTrue(AuditLog::where('action', 'akses_ditolak')->where('user_id', $director->id)->where('subject_id', $doc->id)->exists());
    }

    public function test_guest_presenter_sees_only_own_agenda(): void
    {
        $guest = $this->demo('konsultan');
        $meeting = Meeting::whereHas('participants', fn ($q) => $q->where('user_id', $guest->id))->firstOrFail();
        $visible = $meeting->agendaItems->filter(fn ($i) => Access::canSeeAgendaItem($guest, $i));

        $this->assertNotEmpty($visible);
        $this->assertLessThan($meeting->agendaItems->count(), $visible->count());
        $this->assertTrue($visible->every(fn ($i) => in_array($guest->id, [$i->presenter_id, $i->owner_id], true)));
    }

    public function test_board_book_update_after_distribution_keeps_previous_version(): void
    {
        $meeting = Meeting::whereHas('boardBooks', fn ($q) => $q->where('version_no', 2))->firstOrFail();
        $books = $meeting->boardBooks()->orderBy('version_no')->get();

        $this->assertSame('digantikan', $books[0]->status->value);
        $this->assertSame('terbit', $books[1]->status->value);
        $this->assertNotEmpty($books[1]->change_log);
        $this->assertNotSame($books[0]->manifest_hash, $books[1]->manifest_hash);
    }
}
