<?php

namespace App\Services;

use App\Enums\EvidenceStatus;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\TaskEvidence;
use App\Models\User;
use App\Support\Audit;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Tindak lanjut: tugas hanya selesai bila buktinya diverifikasi pihak berwenang.
 * Kemajuan 100% tidak otomatis menutup tugas.
 */
class TaskService
{
    public function event(Task $task, string $event, ?string $note = null, ?User $user = null): void
    {
        $task->events()->create(['user_id' => $user?->id ?? auth()->id(), 'event' => $event, 'note' => $note, 'created_at' => now()]);
    }

    public function updateProgress(Task $task, int $progress, ?string $note, User $user): void
    {
        $this->assertPic($task, $user);

        if (in_array($task->status, [TaskStatus::Selesai, TaskStatus::Diajukan], true)) {
            throw new RuntimeException('Tugas sedang diverifikasi atau sudah selesai.');
        }

        $task->update([
            'progress' => max(0, min(100, $progress)),
            'status' => $task->status === TaskStatus::BelumMulai ? TaskStatus::Berjalan : $task->status,
        ]);
        $this->event($task, 'kemajuan', "Kemajuan {$progress}%".($note ? ": {$note}" : ''), $user);
    }

    public function submit(Task $task, string $description, ?int $documentId, ?string $filePath, User $user): TaskEvidence
    {
        $this->assertPic($task, $user);

        if ($task->status === TaskStatus::Selesai) {
            throw new RuntimeException('Tugas sudah selesai.');
        }

        return DB::transaction(function () use ($task, $description, $documentId, $filePath, $user) {
            $evidence = $task->evidences()->create([
                'submitted_by' => $user->id,
                'description' => $description,
                'document_id' => $documentId,
                'file_path' => $filePath,
                'status' => EvidenceStatus::Diajukan,
            ]);
            $task->update(['status' => TaskStatus::Diajukan, 'submitted_at' => now(), 'progress' => 100]);
            $this->event($task, 'bukti_diajukan', $description, $user);

            if ($task->verifier) {
                Notification::make()->title('Bukti tindak lanjut menunggu verifikasi')->body($task->title)->icon('heroicon-o-clipboard-document-check')->sendToDatabase($task->verifier);
            }

            Audit::log('bukti_diajukan', "Bukti diajukan untuk {$task->code}", $task);

            return $evidence;
        });
    }

    public function verify(TaskEvidence $evidence, bool $accept, ?string $note, User $user): void
    {
        $task = $evidence->task;

        if ($task->verifier_id && $task->verifier_id !== $user->id) {
            throw new RuntimeException('Hanya verifikator yang ditunjuk yang dapat memverifikasi bukti.');
        }

        if ($task->pic_id === $user->id) {
            throw new RuntimeException('PIC tidak boleh memverifikasi buktinya sendiri.');
        }

        if (! $accept && blank($note)) {
            throw new RuntimeException('Alasan penolakan wajib diisi.');
        }

        DB::transaction(function () use ($evidence, $task, $accept, $note, $user) {
            $evidence->update(['status' => $accept ? EvidenceStatus::Diterima : EvidenceStatus::Ditolak, 'review_note' => $note, 'reviewed_by' => $user->id, 'reviewed_at' => now()]);

            if ($accept) {
                $task->update(['status' => TaskStatus::Selesai, 'verified_at' => now()]);
                $this->event($task, 'diverifikasi', $note ?: 'Bukti diterima; tugas ditutup.', $user);
            } else {
                $task->update(['status' => TaskStatus::Berjalan, 'rejection_count' => $task->rejection_count + 1, 'progress' => min($task->progress, 80)]);
                $this->event($task, 'bukti_ditolak', $note, $user);
            }
        });

        Notification::make()->title($accept ? 'Tindak lanjut diterima' : 'Bukti tindak lanjut ditolak')->body($task->title.($note ? " — {$note}" : ''))
            ->icon($accept ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')->sendToDatabase($task->pic);
        Audit::log($accept ? 'tugas_diverifikasi' : 'bukti_ditolak', "{$task->code}: ".($note ?: 'diterima'), $task);
    }

    public function reopen(Task $task, string $reason, User $user): void
    {
        if ($task->status !== TaskStatus::Selesai && $task->status !== TaskStatus::Diajukan) {
            throw new RuntimeException('Hanya tugas yang sudah diajukan atau selesai yang dapat dibuka kembali.');
        }

        $task->update(['status' => TaskStatus::DibukaKembali, 'verified_at' => null, 'progress' => min($task->progress, 90)]);
        $this->event($task, 'dibuka_kembali', $reason, $user);
        Notification::make()->title('Tindak lanjut dibuka kembali')->body($task->title.' — '.$reason)->icon('heroicon-o-arrow-path')->sendToDatabase($task->pic);
        Audit::log('tugas_dibuka_kembali', "{$task->code}: {$reason}", $task);
    }

    public function isOverdue(Task $task): bool
    {
        return $task->status !== TaskStatus::Selesai && $task->due_date->isPast() && ! $task->due_date->isToday();
    }

    protected function assertPic(Task $task, User $user): void
    {
        if ($task->pic_id !== $user->id) {
            throw new RuntimeException('Hanya PIC yang dapat memperbarui tugas ini.');
        }
    }
}
