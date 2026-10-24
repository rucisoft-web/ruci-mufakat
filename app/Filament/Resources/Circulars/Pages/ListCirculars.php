<?php

namespace App\Filament\Resources\Circulars\Pages;

use App\Filament\Resources\Circulars\CircularResolutionResource;
use App\Services\CircularService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCirculars extends ListRecords
{
    protected static string $resource = CircularResolutionResource::class;

    public function mount(): void
    {
        app(CircularService::class)->expireAll(); // status kedaluwarsa tetap benar walau cron belum berjalan
        parent::mount();
    }

    public function getSubheading(): ?string
    {
        return 'Keputusan di luar rapat sah bila seluruh anggota menyetujui secara tertulis sebelum batas waktu. Satu penolakan menggagalkan usulan.';
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Usulan sirkuler baru')];
    }
}
