<?php

namespace App\Filament\Resources\Meetings\Pages;

use App\Filament\Resources\Meetings\MeetingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListMeetings extends ListRecords
{
    protected static string $resource = MeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Jadwalkan rapat')];
    }

    public function getTabs(): array
    {
        return [
            'mendatang' => Tab::make('Mendatang')->modifyQueryUsing(fn (Builder $query) => $query->where('scheduled_at', '>=', today())->reorder('scheduled_at')),
            'lalu' => Tab::make('Sudah berlangsung')->modifyQueryUsing(fn (Builder $query) => $query->where('scheduled_at', '<', today())),
            'semua' => Tab::make('Semua'),
        ];
    }
}
