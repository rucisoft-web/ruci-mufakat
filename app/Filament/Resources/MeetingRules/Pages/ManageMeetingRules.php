<?php

namespace App\Filament\Resources\MeetingRules\Pages;

use App\Filament\Resources\MeetingRules\MeetingRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMeetingRules extends ManageRecords
{
    protected static string $resource = MeetingRuleResource::class;

    public function getSubheading(): ?string
    {
        return 'Ketentuan POJK 33/2014 dan kebijakan internal disimpan sebagai konfigurasi. Perubahan peraturan cukup diubah di sini.';
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
