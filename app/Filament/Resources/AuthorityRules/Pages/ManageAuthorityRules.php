<?php

namespace App\Filament\Resources\AuthorityRules\Pages;

use App\Filament\Resources\AuthorityRules\AuthorityRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAuthorityRules extends ManageRecords
{
    protected static string $resource = AuthorityRuleResource::class;

    public function getSubheading(): ?string
    {
        return 'Tindakan yang cukup diputus Direksi, yang memerlukan persetujuan atau nasihat Dewan Komisaris, dan yang memerlukan RUPS.';
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
