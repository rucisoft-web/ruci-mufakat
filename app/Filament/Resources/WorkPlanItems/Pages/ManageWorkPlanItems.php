<?php

namespace App\Filament\Resources\WorkPlanItems\Pages;

use App\Filament\Resources\WorkPlanItems\WorkPlanItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageWorkPlanItems extends ManageRecords
{
    protected static string $resource = WorkPlanItemResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
