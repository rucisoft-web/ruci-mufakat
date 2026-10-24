<?php

namespace App\Filament\Resources\AiInteractions\Pages;

use App\Filament\Resources\AiInteractions\AiInteractionResource;
use Filament\Resources\Pages\ManageRecords;

class ManageAiInteractions extends ManageRecords
{
    protected static string $resource = AiInteractionResource::class;

    public function getSubheading(): ?string
    {
        return 'Setiap permintaan AI tercatat: fitur, jumlah halaman sumber yang dikirim, status, dan model. Keluaran tetap berstatus draf.';
    }
}
