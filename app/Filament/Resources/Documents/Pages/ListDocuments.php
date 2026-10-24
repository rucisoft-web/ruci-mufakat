<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    public function getSubheading(): ?string
    {
        return 'Hanya dokumen yang boleh Anda baca yang tampil. Setiap revisi disimpan sebagai versi baru dengan sidik SHA-256; versi lama tidak ditimpa.';
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Unggah dokumen')];
    }
}
