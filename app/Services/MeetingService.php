<?php

namespace App\Services;

use App\Enums\MeetingStatus;
use App\Enums\OrganKind;
use App\Models\Meeting;
use App\Models\Organ;
use App\Support\Audit;
use RuntimeException;

class MeetingService
{
    public function __construct(protected QuorumService $quorum) {}

    /** Undang seluruh anggota organ yang menjabat pada tanggal rapat (gabungan: Direksi + Dekom) dan sekretaris. */
    public function inviteMembers(Meeting $meeting): int
    {
        $organs = $meeting->organ->kind === OrganKind::Gabungan ? $this->quorum->decidingOrgans($meeting) : [$meeting->organ];
        $count = 0;

        foreach ($organs as $organ) {
            /** @var Organ $organ */
            foreach ($organ->memberships()->where('is_active', true)->get() as $m) {
                $p = $meeting->participants()->firstOrCreate(['user_id' => $m->user_id], [
                    'organ_id' => $m->has_vote ? $organ->id : null,
                    'role' => $m->position === 'sekretaris' ? 'sekretaris' : 'anggota',
                    'has_vote' => $m->has_vote,
                ]);
                $count += (int) $p->wasRecentlyCreated;
            }
        }

        return $count;
    }

    public function start(Meeting $meeting): void
    {
        $meeting->update(['status' => MeetingStatus::Berlangsung, 'started_at' => now()]);
        Audit::log('rapat_dimulai', "Rapat dimulai: {$meeting->title}", $meeting);
    }

    /** Tutup rapat. Bila kuorum (kehadiran aktual) tidak terpenuhi, status menjadi gagal kuorum. */
    public function close(Meeting $meeting): MeetingStatus
    {
        if ($meeting->participants()->whereNull('attended')->where('has_vote', true)->exists()) {
            throw new RuntimeException('Lengkapi kehadiran aktual seluruh anggota sebelum menutup rapat.');
        }

        $status = $this->quorum->isMet($meeting) ? MeetingStatus::Selesai : MeetingStatus::GagalKuorum;
        $meeting->update(['status' => $status, 'closed_at' => now()]);
        Audit::log('rapat_ditutup', "Rapat ditutup ({$status->getLabel()}): {$meeting->title}", $meeting);

        return $status;
    }
}
