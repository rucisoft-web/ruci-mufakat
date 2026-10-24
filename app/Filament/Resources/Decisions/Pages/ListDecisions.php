<?php

namespace App\Filament\Resources\Decisions\Pages;

use App\Filament\Resources\Decisions\DecisionResource;
use Filament\Resources\Pages\ListRecords;

class ListDecisions extends ListRecords
{
    protected static string $resource = DecisionResource::class;

    public function getSubheading(): ?string
    {
        return 'Keputusan Direksi, persetujuan dan nasihat Dewan Komisaris, rekomendasi komite, serta keputusan sirkuler, masing-masing dengan suara dan tindak lanjutnya.';
    }
}
