<?php

namespace App\Filament\Resources\InformationRequests\Pages;

use App\Filament\Resources\InformationRequests\InformationRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInformationRequests extends ListRecords
{
    protected static string $resource = InformationRequestResource::class;

    public function getSubheading(): ?string
    {
        return 'Pertanyaan Komisaris kepada manajemen dengan PIC, SLA, jawaban, klarifikasi, dan penutupan oleh penanya.';
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Ajukan pertanyaan')];
    }
}
