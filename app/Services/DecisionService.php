<?php

namespace App\Services;

use App\Enums\DecisionKind;
use App\Enums\DecisionResult;
use App\Enums\OrganKind;
use App\Enums\VoteChoice;
use App\Models\AgendaItem;
use App\Models\ConflictDeclaration;
use App\Models\Decision;
use App\Models\Organ;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Keputusan dan voting.
 *
 * Jenis hasil dipisahkan: Keputusan Direksi, Persetujuan Dewan Komisaris, Nasihat Dewan Komisaris,
 * dan Rekomendasi Komite. Hanya anggota organ yang berwenang, hadir, dan tidak mundur karena
 * benturan kepentingan yang boleh memberi suara. AI tidak pernah memberi suara atau mengesahkan.
 */
class DecisionService
{
    public function __construct(protected QuorumService $quorum) {}

    public function organFor(AgendaItem $item, DecisionKind $kind): Organ
    {
        $meetingOrgan = $item->meeting->organ;

        $target = match ($kind) {
            DecisionKind::KeputusanDireksi => OrganKind::Direksi,
            DecisionKind::PersetujuanDekom, DecisionKind::NasihatDekom => OrganKind::Dekom,
            DecisionKind::RekomendasiKomite => OrganKind::Komite,
        };

        if ($meetingOrgan->kind === $target) {
            return $meetingOrgan;
        }

        if ($meetingOrgan->kind === OrganKind::Gabungan && $target !== OrganKind::Komite) {
            return Organ::where('company_id', $meetingOrgan->company_id)->where('kind', $target->value)->firstOrFail();
        }

        throw new InvalidArgumentException('Jenis keputusan "'.$kind->getLabel().'" tidak dapat diambil dalam rapat '.$meetingOrgan->name.'.');
    }

    /** Anggota yang berhak memberi suara pada agenda ini untuk organ tertentu. */
    public function eligibleVoters(AgendaItem $item, Organ $organ): \Illuminate\Support\Collection
    {
        $recused = ConflictDeclaration::where('agenda_item_id', $item->id)->where('recused', true)->pluck('user_id');

        return $item->meeting->participants()
            ->with('user')
            ->where('has_vote', true)
            ->where('attended', true)
            ->whereIn('user_id', $organ->memberships()->where('is_active', true)->where('has_vote', true)->pluck('user_id'))
            ->whereNotIn('user_id', $recused)
            ->get()
            ->pluck('user');
    }

    /**
     * @param  array<int, string>  $votes  user_id => setuju|tidak_setuju|abstain
     * @param  array<int, string>  $dissents  user_id => catatan berbeda pendapat
     */
    public function record(AgendaItem $item, DecisionKind $kind, array $data, array $votes = [], array $dissents = []): Decision
    {
        $organ = $this->organFor($item, $kind);
        $quorum = collect($this->quorum->evaluate($item->meeting, $item))->firstWhere('organ.id', $organ->id)
            ?? $this->quorum->evaluate($item->meeting, $item)[0];

        if (in_array($kind, [DecisionKind::KeputusanDireksi, DecisionKind::PersetujuanDekom], true) && ! $quorum['met']) {
            throw new InvalidArgumentException("Kuorum {$organ->short_name} pada agenda ini tidak terpenuhi ({$quorum['present']} dari minimal {$quorum['required']}). Keputusan tidak dapat diambil.");
        }

        $eligible = $this->eligibleVoters($item, $organ)->pluck('id');
        $invalid = collect(array_keys($votes))->diff($eligible);

        if ($invalid->isNotEmpty()) {
            throw new InvalidArgumentException('Ada suara dari peserta yang tidak berhak (bukan anggota organ, tidak hadir, atau mundur karena benturan kepentingan).');
        }

        $method = $data['method'] ?? ($votes ? 'voting' : 'musyawarah');
        $result = $data['result'] ?? null;

        if (! $result) {
            $tally = array_count_values($votes);
            $result = ($method === 'voting' && ($tally['setuju'] ?? 0) <= ($tally['tidak_setuju'] ?? 0))
                ? DecisionResult::Ditolak->value
                : ($kind === DecisionKind::NasihatDekom || $kind === DecisionKind::RekomendasiKomite ? DecisionResult::Disampaikan->value : DecisionResult::Disetujui->value);
        }

        return DB::transaction(function () use ($item, $kind, $organ, $data, $votes, $dissents, $method, $result) {
            $decision = Decision::create([
                'organ_id' => $organ->id,
                'meeting_id' => $item->meeting_id,
                'agenda_item_id' => $item->id,
                'number' => $data['number'] ?? $this->nextNumber($organ, $kind),
                'kind' => $kind,
                'title' => $data['title'] ?? $item->title,
                'text' => $data['text'],
                'method' => $method,
                'result' => $result,
                'conditions' => $data['conditions'] ?? null,
                'basis' => $data['basis'] ?? $item->authority_basis,
                'decided_on' => $item->meeting->scheduled_at->toDateString(),
                'effective_on' => $data['effective_on'] ?? $item->meeting->scheduled_at->toDateString(),
                'data_origin' => $data['data_origin'] ?? 'simulated',
            ]);

            foreach ($votes as $userId => $vote) {
                $decision->votes()->create([
                    'user_id' => $userId,
                    'vote' => VoteChoice::from($vote),
                    'dissent_note' => $dissents[$userId] ?? null,
                ]);
            }

            $item->update(['status' => 'selesai']);
            Audit::log('keputusan_dicatat', "{$kind->getLabel()} {$decision->number}: {$decision->title}", $decision);

            return $decision;
        });
    }

    public function nextNumber(Organ $organ, DecisionKind $kind): string
    {
        $prefix = match ($kind) {
            DecisionKind::KeputusanDireksi => 'KEP-DIR',
            DecisionKind::PersetujuanDekom => 'PST-DK',
            DecisionKind::NasihatDekom => 'NSH-DK',
            DecisionKind::RekomendasiKomite => 'REK-'.strtoupper(preg_replace('/[^A-Za-z]/', '', $organ->short_name)),
        };
        $year = now()->year;
        $count = Decision::where('kind', $kind->value)->whereYear('decided_on', $year)->count() + 1;

        return sprintf('%s/%03d/%d', $prefix, $count, $year);
    }

    /** @return array{setuju:int, tidak_setuju:int, abstain:int} */
    public function tally(Decision $decision): array
    {
        $counts = $decision->votes->countBy(fn ($v) => $v->vote->value);

        return ['setuju' => $counts['setuju'] ?? 0, 'tidak_setuju' => $counts['tidak_setuju'] ?? 0, 'abstain' => $counts['abstain'] ?? 0];
    }
}
