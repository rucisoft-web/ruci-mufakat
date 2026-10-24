<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use App\Services\TaskService;
use Filament\Resources\Pages\EditRecord;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    protected function afterSave(): void
    {
        if ($this->record->wasChanged('due_date')) {
            app(TaskService::class)->event($this->record, 'tenggat_diubah', 'Tenggat menjadi '.$this->record->due_date->format('d M Y'));
        }
    }
}
