<?php

namespace App\Services;

use App\Enums\InfoRequestStatus;
use App\Models\InformationRequest;
use App\Models\User;
use App\Support\Audit;
use Filament\Notifications\Notification;
use RuntimeException;

/**
 * Permintaan informasi Dewan Komisaris/komite kepada manajemen:
 * diajukan → dijawab → (klarifikasi → dijawab)* → diterima.
 */
class InformationRequestService
{
    public function answer(InformationRequest $request, User $user, string $body, ?int $documentId = null): void
    {
        if ($request->pic_id !== $user->id) {
            throw new RuntimeException('Hanya PIC yang ditunjuk yang dapat menjawab.');
        }

        if ($request->status === InfoRequestStatus::Diterima) {
            throw new RuntimeException('Permintaan ini sudah ditutup.');
        }

        $request->messages()->create(['user_id' => $user->id, 'kind' => 'jawaban', 'body' => $body, 'document_id' => $documentId]);
        $request->update(['status' => InfoRequestStatus::Dijawab, 'answered_at' => now()]);
        Notification::make()->title('Permintaan informasi dijawab')->body($request->subject)->icon('heroicon-o-chat-bubble-left-right')->sendToDatabase($request->requester);
        Audit::log('info_dijawab', "Jawaban {$request->code}", $request);
    }

    public function clarify(InformationRequest $request, User $user, string $body): void
    {
        $this->assertRequester($request, $user);
        $request->messages()->create(['user_id' => $user->id, 'kind' => 'klarifikasi', 'body' => $body]);
        $request->update(['status' => InfoRequestStatus::Klarifikasi]);
        Notification::make()->title('Klarifikasi diminta')->body($request->subject)->icon('heroicon-o-question-mark-circle')->sendToDatabase($request->pic);
        Audit::log('info_klarifikasi', "Klarifikasi {$request->code}", $request);
    }

    public function accept(InformationRequest $request, User $user, ?string $note = null): void
    {
        $this->assertRequester($request, $user);

        if ($request->status !== InfoRequestStatus::Dijawab) {
            throw new RuntimeException('Hanya permintaan yang sudah dijawab yang dapat diterima.');
        }

        $request->messages()->create(['user_id' => $user->id, 'kind' => 'penerimaan', 'body' => $note ?: 'Jawaban diterima.']);
        $request->update(['status' => InfoRequestStatus::Diterima, 'accepted_at' => now()]);
        Audit::log('info_diterima', "Permintaan {$request->code} ditutup", $request);
    }

    /** Masih menunggu jawaban manajemen dan sudah melewati SLA. */
    public function isOverdue(InformationRequest $request): bool
    {
        return in_array($request->status, [InfoRequestStatus::Diajukan, InfoRequestStatus::Klarifikasi], true) && $request->sla_due_at->isPast();
    }

    public function answeredLate(InformationRequest $request): bool
    {
        return $request->answered_at !== null && $request->answered_at->greaterThan($request->sla_due_at);
    }

    protected function assertRequester(InformationRequest $request, User $user): void
    {
        if ($request->requester_id !== $user->id && ! $user->isSecretariat()) {
            throw new RuntimeException('Hanya penanya atau sekretariat yang dapat melakukan ini.');
        }
    }
}
