<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCompanies extends ManageRecords
{
    protected static string $resource = CompanyResource::class;

    public function getSubheading(): ?string
    {
        return 'Perusahaan induk dan entitas anak. Profil Kimia Farma mengacu pada Laporan Tahunan 2025.';
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
