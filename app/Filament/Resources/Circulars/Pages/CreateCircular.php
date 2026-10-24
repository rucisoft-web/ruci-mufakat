<?php

namespace App\Filament\Resources\Circulars\Pages;

use App\Filament\Resources\Circulars\CircularResolutionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCircular extends CreateRecord
{
    protected static string $resource = CircularResolutionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data + ['status' => 'draf', 'created_by' => auth()->id()];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }
}
