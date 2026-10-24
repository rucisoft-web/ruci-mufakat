<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Pages\RuangBaca;
use App\Filament\Resources\Documents\DocumentResource;
use App\Support\Access;
use Filament\Resources\Pages\CreateRecord;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data + ['uploaded_by' => auth()->id(), 'version_no' => 1];
    }

    protected function afterCreate(): void
    {
        DocumentResource::ingest($this->record, $this->data['text'] ?? null);
        Access::flush();
    }

    protected function getRedirectUrl(): string
    {
        return RuangBaca::getUrl(['dokumen' => $this->record->id]);
    }
}
