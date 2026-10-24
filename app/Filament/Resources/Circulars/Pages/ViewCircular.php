<?php

namespace App\Filament\Resources\Circulars\Pages;

use App\Enums\CircularStatus;
use App\Filament\Resources\Circulars\CircularResolutionResource;
use App\Services\CircularService;
use App\Support\Access;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Throwable;

class ViewCircular extends ViewRecord
{
    protected static string $resource = CircularResolutionResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);
        app(CircularService::class)->evaluate($this->record);
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
        $c = $this->getRecord();
        $svc = app(CircularService::class);
        $mine = $c->responses->firstWhere('user_id', $me->id);
        $canRespond = ! $me->isReadOnly() && $c->status === CircularStatus::Beredar && $mine?->response === 'menunggu';

        return [
            Action::make('circulate')->label('Edarkan')->icon('heroicon-o-paper-airplane')
                ->visible($c->status === CircularStatus::Draf && Access::canManageAnyMeeting($me) && Access::managedOrganIds($me)->contains($c->organ_id))
                ->requiresConfirmation()
                ->action(fn () => $this->run(fn () => $svc->circulate($c), 'Usulan diedarkan kepada seluruh anggota.')),
            Action::make('approve')->label('Setuju')->icon('heroicon-o-hand-thumb-up')->color('success')->visible($canRespond)
                ->schema([Textarea::make('note')->label('Catatan (opsional)')])
                ->action(fn (array $data) => $this->run(fn () => $svc->respond($c, $me, true, $data['note'] ?? null), 'Persetujuan tertulis dicatat.')),
            Action::make('reject')->label('Tidak setuju')->icon('heroicon-o-hand-thumb-down')->color('danger')->visible($canRespond)
                ->schema([Textarea::make('note')->label('Alasan')->required()])
                ->action(fn (array $data) => $this->run(fn () => $svc->respond($c, $me, false, $data['note']), 'Tanggapan dicatat. Usulan sirkuler tidak sah.')),
        ];
    }
}
