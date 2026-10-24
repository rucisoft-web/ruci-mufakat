<?php

namespace App\Filament\Resources\HistoricalOfficials\Pages;

use App\Filament\Resources\HistoricalOfficials\HistoricalOfficialResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageHistoricalOfficials extends ManageRecords
{
    protected static string $resource = HistoricalOfficialResource::class;

    public function getSubheading(): ?string
    {
        return 'Nama sebenarnya hanya dipakai di direktori ini (posisi per 31 Desember 2025, sumber publik). Seluruh kegiatan rapat di aplikasi memakai alias "Demo …".';
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
