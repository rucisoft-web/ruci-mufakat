<?php

namespace App\Filament\Resources\InformationRequests\Pages;

use App\Enums\InfoRequestStatus;
use App\Filament\Resources\InformationRequests\InformationRequestResource;
use App\Models\Document;
use App\Models\InformationRequest;
use App\Services\InformationRequestService;
use App\Support\Access;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Throwable;

class ViewInformationRequest extends ViewRecord
{
    protected static string $resource = InformationRequestResource::class;

    public function mount(int|string $record): void
    {
        $r = InformationRequest::find($record);

        if ($r && ! Access::scopeInfoRequests(InformationRequest::query(), auth()->user())->whereKey($r->id)->exists()) {
            Access::deny(auth()->user(), 'membuka permintaan informasi '.$r->code, $r);
            abort(403);
        }

        parent::mount($record);
    }

    public function getTitle(): string
    {
        return $this->getRecord()->code.' · '.$this->getRecord()->subject;
    }

    protected function run(callable $fn, string $ok): void
    {
        try {
            $fn();
            Notification::make()->title($ok)->success()->send();
            $this->record->refresh();
        } catch (Throwable $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    protected function getHeaderActions(): array
    {
        $me = auth()->user();
        $r = $this->getRecord();
        $svc = app(InformationRequestService::class);
        $open = $r->status !== InfoRequestStatus::Diterima && ! $me->isReadOnly();
        $asker = $r->requester_id === $me->id || $me->isSecretariat();

        return [
            Action::make('answer')->label('Jawab')->icon('heroicon-o-chat-bubble-bottom-center-text')->color('success')
                ->visible($open && $r->pic_id === $me->id && in_array($r->status, [InfoRequestStatus::Diajukan, InfoRequestStatus::Klarifikasi], true))
                ->schema([
                    Textarea::make('body')->label('Jawaban')->required()->rows(5),
                    Select::make('document_id')->label('Lampiran dari pustaka')->searchable()
                        ->options(fn () => Document::whereIn('id', Access::readableDocumentIds($me))->pluck('title', 'id')),
                ])
                ->action(fn (array $data) => $this->run(fn () => $svc->answer($r, $me, $data['body'], $data['document_id'] ?? null), 'Jawaban terkirim kepada penanya.')),
            Action::make('clarify')->label('Minta klarifikasi')->icon('heroicon-o-question-mark-circle')->color('warning')
                ->visible($open && $asker && $r->status === InfoRequestStatus::Dijawab)
                ->schema([Textarea::make('body')->label('Hal yang perlu diperjelas')->required()])
                ->action(fn (array $data) => $this->run(fn () => $svc->clarify($r, $me, $data['body']), 'Klarifikasi diminta.')),
            Action::make('accept')->label('Terima & tutup')->icon('heroicon-o-check-circle')
                ->visible($open && $asker && $r->status === InfoRequestStatus::Dijawab)
                ->schema([Textarea::make('note')->label('Catatan (opsional)')])
                ->action(fn (array $data) => $this->run(fn () => $svc->accept($r, $me, $data['note'] ?? null), 'Permintaan ditutup.')),
        ];
    }
}
