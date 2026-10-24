<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use App\Services\TaskService;
use App\Support\Audit;
use Filament\Resources\Pages\CreateRecord;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data + ['original_due_date' => $data['due_date'], 'status' => 'belum_mulai', 'data_origin' => 'simulated'];
    }

    protected function afterCreate(): void
    {
        app(TaskService::class)->event($this->record, 'dibuat', 'Tugas dibuat oleh '.auth()->user()->name);
        Audit::log('tugas_dibuat', "Tugas {$this->record->code} dibuat", $this->record);
    }
}
