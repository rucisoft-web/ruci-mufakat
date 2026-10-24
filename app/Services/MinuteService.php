<?php

namespace App\Services;

use App\Enums\MinuteStatus;
use App\Enums\SignatureStatus;
use App\Models\Meeting;
use App\Models\Minute;
use App\Models\User;
use App\Support\Audit;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Risalah: draf → reviu → edaran tanda tangan → final.
 * Risalah final tidak pernah ditimpa; perubahan sesudahnya dibuat sebagai addendum/koreksi yang
 * tertaut ke risalah sebelumnya.
 */
class MinuteService
{
    public function __construct(protected QuorumService $quorum, protected DecisionService $decisions) {}

    public function generateBody(Meeting $meeting): string
    {
        $meeting->load(['organ', 'chair', 'secretary', 'participants.user', 'agendaItems.decisions.votes.user', 'agendaItems.conflicts.user', 'agendaItems.presenter']);
        $present = $meeting->participants->where('attended', true);
        $absent = $meeting->participants->where('attended', false);
        $lines = [];

        $lines[] = '# Risalah '.$meeting->title;
        $lines[] = '';
        $lines[] = '**Nomor:** '.($meeting->number ?? '—').'  ';
        $lines[] = '**Waktu:** '.$meeting->scheduled_at->translatedFormat('l, d F Y, H:i').' WIB  ';
        $lines[] = '**Tempat/media:** '.($meeting->location ?? '—').' ('.$meeting->mode.')  ';
        $lines[] = '**Pimpinan rapat:** '.($meeting->chair?->name ?? '—').'  ';
        $lines[] = '**Sekretaris rapat:** '.($meeting->secretary?->name ?? '—');
        $lines[] = '';
        $lines[] = '## Kehadiran aktual';
        foreach ($present as $p) {
            $lines[] = "- {$p->user->name} ({$p->role})".($p->check_in_at ? ', masuk '.$p->check_in_at->format('H:i') : '').($p->check_out_at ? ', keluar '.$p->check_out_at->format('H:i') : '');
        }
        foreach ($absent as $p) {
            $lines[] = "- Tidak hadir: {$p->user->name}".($p->absence_reason ? " — {$p->absence_reason}" : '');
        }
        $lines[] = '';
        $lines[] = '**Kuorum:** '.$this->quorum->summary($meeting);
        $lines[] = '';
        $lines[] = '## Pembahasan dan hasil';

        foreach ($meeting->agendaItems->sortBy('seq') as $item) {
            $lines[] = '';
            $lines[] = "### {$item->seq}. {$item->title}";
            $lines[] = 'Penyaji: '.($item->presenter?->name ?? '—').' · Sifat: '.$item->purpose->getLabel();

            foreach ($item->conflicts as $c) {
                $lines[] = "> Benturan kepentingan: {$c->user->name} menyatakan {$c->nature}".($c->recused ? ' dan tidak mengikuti pembahasan serta pengambilan keputusan agenda ini.' : '.');
            }

            $lines[] = '';
            $lines[] = $item->discussion ?: '_(Pokok pembahasan belum diisi.)_';

            foreach ($item->decisions as $d) {
                $t = $this->decisions->tally($d);
                $lines[] = '';
                $lines[] = "**{$d->kind->getLabel()} {$d->number}** — {$d->result->getLabel()} ({$d->method})";
                $lines[] = $d->text;
                if ($d->votes->isNotEmpty()) {
                    $lines[] = "Suara: setuju {$t['setuju']}, tidak setuju {$t['tidak_setuju']}, abstain {$t['abstain']}.";
                }
                foreach ($d->votes->whereNotNull('dissent_note') as $v) {
                    $lines[] = "> Pendapat berbeda ({$v->user->name}): {$v->dissent_note}";
                }
            }
        }

        return implode("\n", $lines);
    }

    public function createDraft(Meeting $meeting, User $user): Minute
    {
        return Minute::create([
            'meeting_id' => $meeting->id,
            'version_no' => (int) $meeting->minutes()->max('version_no') + 1,
            'status' => MinuteStatus::Draf,
            'body' => $this->generateBody($meeting),
            'drafted_by' => $user->id,
        ]);
    }

    public function sendForReview(Minute $minute): void
    {
        $this->assertNotFinal($minute);
        $minute->update(['status' => MinuteStatus::Reviu]);
        Audit::log('risalah_reviu', 'Draf risalah dikirim untuk reviu peserta', $minute);
    }

    /** Edaran tanda tangan untuk anggota yang hadir, pimpinan, dan sekretaris rapat. */
    public function circulate(Minute $minute): void
    {
        $this->assertNotFinal($minute);
        $meeting = $minute->meeting;
        $signers = $meeting->participants()->where('attended', true)->where('has_vote', true)->pluck('user_id')
            ->push($meeting->chair_id, $meeting->secretary_id)->filter()->unique();

        DB::transaction(function () use ($minute, $signers) {
            foreach ($signers as $userId) {
                $minute->signatures()->firstOrCreate(['user_id' => $userId], ['status' => SignatureStatus::Menunggu]);
            }
            $minute->update(['status' => MinuteStatus::EdarTtd, 'circulated_at' => now(), 'content_hash' => hash('sha256', $minute->body)]);
        });

        foreach (User::whereIn('id', $signers)->get() as $u) {
            Notification::make()->title('Risalah menunggu tanda tangan Anda')->body($meeting->title)->icon('heroicon-o-pencil-square')->sendToDatabase($u);
        }

        Audit::log('risalah_edar', 'Risalah diedarkan untuk tanda tangan ('.$signers->count().' penanda tangan)', $minute);
    }

    public function sign(Minute $minute, User $user): void
    {
        $this->respond($minute, $user, SignatureStatus::Ditandatangani, null);
    }

    public function refuse(Minute $minute, User $user, string $reason): void
    {
        $this->respond($minute, $user, SignatureStatus::Menolak, $reason);
    }

    protected function respond(Minute $minute, User $user, SignatureStatus $status, ?string $reason): void
    {
        if ($minute->status !== MinuteStatus::EdarTtd) {
            throw new RuntimeException('Risalah tidak sedang dalam edaran tanda tangan.');
        }

        if ($minute->content_hash !== hash('sha256', $minute->body)) {
            throw new RuntimeException('Isi risalah berubah setelah diedarkan. Edarkan ulang.');
        }

        $signature = $minute->signatures()->where('user_id', $user->id)->firstOrFail();
        $signature->update(['status' => $status, 'reason' => $reason, 'signed_at' => now(), 'provider_ref' => 'SIM-'.strtoupper(substr(sha1($minute->id.$user->id.now()), 0, 10))]);
        Audit::log($status === SignatureStatus::Ditandatangani ? 'risalah_ditandatangani' : 'risalah_tidak_ditandatangani', $user->name.($reason ? " — {$reason}" : ''), $minute);

        if ($minute->signatures()->where('status', SignatureStatus::Menunggu->value)->doesntExist()) {
            $this->finalize($minute);
        }
    }

    public function finalize(Minute $minute): void
    {
        $chairSigned = $minute->signatures()->where('user_id', $minute->meeting->chair_id)->where('status', SignatureStatus::Ditandatangani->value)->exists();

        if (! $chairSigned) {
            throw new RuntimeException('Risalah baru dapat difinalkan setelah pimpinan rapat menandatangani.');
        }

        $minute->update(['status' => MinuteStatus::Final, 'finalized_at' => now(), 'content_hash' => hash('sha256', $minute->body)]);
        Audit::log('risalah_final', 'Risalah ditetapkan final', $minute);
    }

    /** Koreksi setelah final: addendum baru yang menunjuk risalah asal. */
    public function correct(Minute $final, string $reason, string $body, User $user): Minute
    {
        if ($final->status !== MinuteStatus::Final) {
            throw new RuntimeException('Hanya risalah final yang dikoreksi melalui addendum. Draf cukup diubah langsung.');
        }

        $addendum = Minute::create([
            'meeting_id' => $final->meeting_id,
            'corrects_id' => $final->id,
            'version_no' => (int) Minute::where('meeting_id', $final->meeting_id)->max('version_no') + 1,
            'kind' => 'addendum',
            'status' => MinuteStatus::Draf,
            'body' => $body,
            'correction_reason' => $reason,
            'drafted_by' => $user->id,
        ]);

        Audit::log('risalah_addendum', "Addendum atas risalah v{$final->version_no}: {$reason}", $addendum);

        return $addendum;
    }

    public function assertNotFinal(Minute $minute): void
    {
        if ($minute->status === MinuteStatus::Final) {
            throw new RuntimeException('Risalah final tidak dapat diubah. Gunakan koreksi/addendum.');
        }
    }
}
