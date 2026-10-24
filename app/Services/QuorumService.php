<?php

namespace App\Services;

use App\Enums\OrganKind;
use App\Models\AgendaItem;
use App\Models\ConflictDeclaration;
use App\Models\Meeting;
use App\Models\Organ;

/**
 * Kuorum dihitung dari kehadiran AKTUAL (bukan RSVP), terpisah per organ.
 *
 * Pada rapat gabungan, kuorum Direksi dan kuorum Dewan Komisaris dinilai sendiri-sendiri;
 * suara seluruh peserta tidak pernah digabung untuk mengesahkan keputusan salah satu organ.
 * Anggota yang mundur karena benturan kepentingan tidak dihitung pada agenda itu,
 * kecuali kebijakan perusahaan menyatakan tetap dihitung (counts_for_quorum).
 */
class QuorumService
{
    /** Organ yang kuorumnya dinilai pada rapat ini. */
    public function decidingOrgans(Meeting $meeting): array
    {
        $organ = $meeting->organ;

        if ($organ->kind !== OrganKind::Gabungan) {
            return [$organ];
        }

        return Organ::where('company_id', $organ->company_id)
            ->whereIn('kind', [OrganKind::Direksi->value, OrganKind::Dekom->value])
            ->orderBy('sort')->get()->all();
    }

    public function totalVotingMembers(Organ $organ, Meeting $meeting): int
    {
        $date = $meeting->scheduled_at->toDateString();

        return $organ->memberships()
            ->where('has_vote', true)
            ->where(fn ($q) => $q->whereNull('term_start')->orWhereDate('term_start', '<=', $date))
            ->where(fn ($q) => $q->whereNull('term_end')->orWhereDate('term_end', '>=', $date))
            ->count();
    }

    public function required(Organ $organ, int $total): int
    {
        $fraction = $total * $organ->quorum_numerator / max(1, $organ->quorum_denominator);

        return $organ->quorum_strictly_more ? (int) floor($fraction) + 1 : (int) ceil($fraction);
    }

    /**
     * @return array<int, array{organ: Organ, total: int, present: int, recused: int, required: int, met: bool}>
     */
    public function evaluate(Meeting $meeting, ?AgendaItem $item = null): array
    {
        $result = [];

        foreach ($this->decidingOrgans($meeting) as $organ) {
            $presentIds = $meeting->participants()
                ->where('has_vote', true)
                ->where('attended', true)
                ->whereIn('user_id', $organ->memberships()->pluck('user_id'))
                ->pluck('user_id');

            $recused = $item
                ? ConflictDeclaration::where('agenda_item_id', $item->id)->where('recused', true)->where('counts_for_quorum', false)
                    ->whereIn('user_id', $presentIds)->count()
                : 0;

            $total = $this->totalVotingMembers($organ, $meeting);
            $present = $presentIds->count() - $recused;
            $required = $this->required($organ, $total);

            $result[] = [
                'organ' => $organ,
                'total' => $total,
                'present' => $present,
                'recused' => $recused,
                'required' => $required,
                'met' => $total > 0 && $present >= $required,
            ];
        }

        return $result;
    }

    public function isMet(Meeting $meeting): bool
    {
        // Rapat gabungan sah dibuka bila sekurang-kurangnya kuorum satu organ terpenuhi;
        // keputusan tiap organ tetap memerlukan kuorum organ masing-masing.
        $evaluations = $this->evaluate($meeting);

        return $meeting->organ->kind === OrganKind::Gabungan
            ? collect($evaluations)->contains('met', true)
            : (bool) ($evaluations[0]['met'] ?? false);
    }

    public function summary(Meeting $meeting, ?AgendaItem $item = null): string
    {
        return collect($this->evaluate($meeting, $item))->map(fn ($e) => sprintf(
            '%s: %d dari %d hadir (minimal %d)%s — %s',
            $e['organ']->short_name, $e['present'], $e['total'], $e['required'],
            $e['recused'] ? ", {$e['recused']} mundur karena benturan kepentingan" : '',
            $e['met'] ? 'kuorum terpenuhi' : 'TIDAK kuorum',
        ))->implode('; ');
    }
}
