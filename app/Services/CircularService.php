<?php

namespace App\Services;

use App\Enums\CircularStatus;
use App\Enums\DecisionKind;
use App\Enums\OrganKind;
use App\Models\CircularResolution;
use App\Models\Decision;
use App\Models\User;
use App\Support\Audit;
use Filament\Notifications\Notification;
use RuntimeException;

/**
 * Keputusan sirkuler (di luar rapat): sah bila SELURUH anggota yang berhak menyetujui secara tertulis
 * sebelum batas waktu. Satu penolakan menggagalkan; lewat batas waktu menjadi kedaluwarsa.
 */
class CircularService
{
    public function circulate(CircularResolution $circular): void
    {
        $voters = $circular->organ->memberships()->where('is_active', true)->where('has_vote', true)->pluck('user_id');

        foreach ($voters as $userId) {
            $circular->responses()->firstOrCreate(['user_id' => $userId], ['response' => 'menunggu']);
        }

        $circular->update(['status' => CircularStatus::Beredar, 'circulated_at' => now()]);

        foreach (User::whereIn('id', $voters)->get() as $u) {
            Notification::make()->title('Usulan keputusan sirkuler')->body($circular->title.' — batas '.$circular->deadline_at->translatedFormat('d M Y H:i'))->icon('heroicon-o-arrow-path-rounded-square')->sendToDatabase($u);
        }

        Audit::log('sirkuler_edar', "Sirkuler {$circular->number} diedarkan ke {$voters->count()} anggota", $circular);
    }

    public function respond(CircularResolution $circular, User $user, bool $approve, ?string $note): void
    {
        $this->evaluate($circular);

        if ($circular->status !== CircularStatus::Beredar) {
            throw new RuntimeException('Usulan sirkuler ini sudah tidak menerima tanggapan ('.$circular->status->getLabel().').');
        }

        $response = $circular->responses()->where('user_id', $user->id)->first();

        if (! $response) {
            throw new RuntimeException('Anda bukan pihak yang berhak memberi persetujuan sirkuler ini.');
        }

        $response->update(['response' => $approve ? 'setuju' : 'tidak_setuju', 'note' => $note, 'responded_at' => now()]);
        Audit::log('sirkuler_tanggapan', $user->name.': '.($approve ? 'setuju' : 'tidak setuju'), $circular);
        $this->evaluate($circular->fresh());
    }

    public function evaluate(CircularResolution $circular): CircularResolution
    {
        if ($circular->status !== CircularStatus::Beredar) {
            return $circular;
        }

        $responses = $circular->responses;

        if ($responses->contains('response', 'tidak_setuju')) {
            $circular->update(['status' => CircularStatus::Ditolak, 'closed_at' => now()]);
        } elseif ($responses->isNotEmpty() && $responses->every(fn ($r) => $r->response === 'setuju')) {
            $circular->update(['status' => CircularStatus::Disetujui, 'closed_at' => now()]);
            $this->toDecision($circular);
        } elseif ($circular->deadline_at->isPast()) {
            $circular->update(['status' => CircularStatus::Kedaluwarsa, 'closed_at' => $circular->deadline_at]);
            Audit::log('sirkuler_kedaluwarsa', "Sirkuler {$circular->number} kedaluwarsa: ".$responses->where('response', 'menunggu')->count().' anggota belum menanggapi', $circular, [], null);
        }

        return $circular;
    }

    protected function toDecision(CircularResolution $circular): Decision
    {
        $kind = $circular->organ->kind === OrganKind::Dekom ? DecisionKind::PersetujuanDekom : DecisionKind::KeputusanDireksi;

        return Decision::create([
            'organ_id' => $circular->organ_id,
            'circular_resolution_id' => $circular->id,
            'number' => app(DecisionService::class)->nextNumber($circular->organ, $kind).'-S',
            'kind' => $kind,
            'title' => $circular->title,
            'text' => $circular->body,
            'method' => 'sirkuler',
            'result' => 'disetujui',
            'basis' => 'Keputusan di luar rapat, disetujui tertulis oleh seluruh anggota',
            'decided_on' => now()->toDateString(),
            'effective_on' => now()->toDateString(),
            'data_origin' => $circular->data_origin,
        ]);
    }

    public function expireAll(): int
    {
        $count = 0;

        foreach (CircularResolution::where('status', CircularStatus::Beredar->value)->get() as $c) {
            $count += (int) ($this->evaluate($c)->status !== CircularStatus::Beredar);
        }

        return $count;
    }
}
