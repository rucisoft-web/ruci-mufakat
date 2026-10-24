<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Enums\EvidenceStatus;
use App\Enums\TaskStatus;
use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use App\Support\Access;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Throwable;

class ViewTask extends ViewRecord
{
    protected static string $resource = TaskResource::class;

    public function mount(int|string $record): void
    {
        $task = Task::find($record);

        if ($task && ! Access::scopeTasks(Task::query(), auth()->user())->whereKey($task->id)->exists()) {
            Access::deny(auth()->user(), 'membuka tugas '.$task->code, $task);
            abort(403, 'Anda tidak berhak membuka tugas ini.');
        }

        parent::mount($record);
    }

    public function getTitle(): string
    {
        return $this->getRecord()->code.' · '.$this->getRecord()->title;
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
        /** @var User $me */
        $me = auth()->user();
        /** @var Task $task */
        $task = $this->getRecord();
        $svc = app(TaskService::class);
        $isPic = $task->pic_id === $me->id && ! $me->isReadOnly();
        $pending = $task->evidences()->where('status', EvidenceStatus::Diajukan->value)->latest()->first();
        $canVerify = ! $me->isReadOnly() && $task->pic_id !== $me->id && ($task->verifier_id ? $task->verifier_id === $me->id : $me->isSecretariat());

        return [
            Action::make('progress')->label('Perbarui kemajuan')->icon('heroicon-o-arrow-trending-up')
                ->visible($isPic && ! in_array($task->status, [TaskStatus::Selesai, TaskStatus::Diajukan], true))
                ->fillForm(['progress' => $task->progress])
                ->schema([
                    TextInput::make('progress')->label('Kemajuan (%)')->numeric()->minValue(0)->maxValue(100)->required(),
                    Textarea::make('note')->label('Catatan'),
                ])
                ->action(fn (array $data) => $this->run(fn () => $svc->updateProgress($task, (int) $data['progress'], $data['note'] ?? null, $me), 'Kemajuan diperbarui.')),
            Action::make('submit')->label('Ajukan bukti')->icon('heroicon-o-paper-clip')->color('success')
                ->visible($isPic && $task->status !== TaskStatus::Selesai && $task->status !== TaskStatus::Diajukan)
                ->schema([
                    Textarea::make('description')->label('Uraian bukti')->required(),
                    Select::make('document_id')->label('Dokumen di pustaka (opsional)')->searchable()
                        ->options(fn () => \App\Models\Document::whereIn('id', Access::readableDocumentIds($me))->latest()->limit(200)->pluck('title', 'id')),
                    FileUpload::make('file')->label('Unggah berkas (opsional)')->disk('local')->directory('bukti')->maxSize((int) config('mufakat.max_upload_kb')),
                ])
                ->modalDescription('Setelah diajukan, tugas menunggu verifikasi. Anda tidak dapat memverifikasi bukti sendiri.')
                ->action(fn (array $data) => $this->run(fn () => $svc->submit($task, $data['description'], $data['document_id'] ?? null, $data['file'] ?? null, $me), 'Bukti diajukan untuk verifikasi.')),
            Action::make('accept')->label('Terima bukti')->icon('heroicon-o-check-circle')->color('success')
                ->visible($canVerify && $pending !== null)
                ->schema([Textarea::make('note')->label('Catatan verifikasi')])
                ->modalDescription(fn () => 'Bukti: '.$pending?->description)
                ->action(fn (array $data) => $this->run(fn () => $svc->verify($pending, true, $data['note'] ?? null, $me), 'Bukti diterima; tugas selesai.')),
            Action::make('reject')->label('Tolak bukti')->icon('heroicon-o-x-circle')->color('danger')
                ->visible($canVerify && $pending !== null)
                ->schema([Textarea::make('note')->label('Alasan penolakan')->required()])
                ->action(fn (array $data) => $this->run(fn () => $svc->verify($pending, false, $data['note'], $me), 'Bukti ditolak; tugas kembali berjalan.')),
            Action::make('reopen')->label('Buka kembali')->icon('heroicon-o-arrow-path')->color('warning')
                ->visible(! $me->isReadOnly() && ($canVerify || $me->isSecretariat()) && $task->status === TaskStatus::Selesai)
                ->schema([Textarea::make('reason')->label('Alasan')->required()])
                ->action(fn (array $data) => $this->run(fn () => $svc->reopen($task, $data['reason'], $me), 'Tugas dibuka kembali.')),
            EditAction::make()->label('Ubah'),
        ];
    }
}
