<?php

namespace App\Filament\Resources\Decisions\Pages;

use App\Filament\Resources\Decisions\DecisionResource;
use App\Models\Decision;
use App\Support\Access;
use Filament\Resources\Pages\ViewRecord;

class ViewDecision extends ViewRecord
{
    protected static string $resource = DecisionResource::class;

    public function mount(int|string $record): void
    {
        $decision = Decision::find($record);

        if ($decision && ! Access::decisionOrganIds(auth()->user())->contains($decision->organ_id)) {
            Access::deny(auth()->user(), 'membuka keputusan '.$decision->number, $decision);
            abort(403, 'Anda tidak berhak membuka keputusan ini.');
        }

        parent::mount($record);
    }

    public function getTitle(): string
    {
        return $this->getRecord()->number ?? 'Keputusan';
    }
}
