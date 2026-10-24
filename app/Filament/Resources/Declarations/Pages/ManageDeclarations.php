<?php

namespace App\Filament\Resources\Declarations\Pages;

use App\Filament\Resources\Declarations\DeclarationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDeclarations extends ManageRecords
{
    protected static string $resource = DeclarationResource::class;

    public function getSubheading(): ?string
    {
        return 'Pernyataan independensi, benturan kepentingan tahunan, kepemilikan saham, dan rangkap jabatan.';
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
