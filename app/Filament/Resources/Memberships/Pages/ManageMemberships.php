<?php

namespace App\Filament\Resources\Memberships\Pages;

use App\Filament\Resources\Memberships\MembershipResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMemberships extends ManageRecords
{
    protected static string $resource = MembershipResource::class;

    public function getSubheading(): ?string
    {
        return 'Komposisi Direksi, Dewan Komisaris, dan komite dengan masa jabatan. Baris kuning: masa jabatan berakhir dalam 12 bulan. Nama "Demo …" adalah alias untuk kegiatan simulasi.';
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
