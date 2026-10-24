<?php

namespace App\Filament\Resources\Organs\Pages;

use App\Filament\Resources\Organs\OrganResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageOrgans extends ManageRecords
{
    protected static string $resource = OrganResource::class;

    public function getSubheading(): ?string
    {
        return 'Kuorum, frekuensi rapat minimum, dan batas penyampaian bahan disimpan per organ sebagai konfigurasi.';
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
