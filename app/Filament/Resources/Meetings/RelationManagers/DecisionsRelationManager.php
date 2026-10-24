<?php

namespace App\Filament\Resources\Meetings\RelationManagers;

use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Decisions\DecisionResource;
use App\Models\Decision;
use App\Services\DecisionService;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DecisionsRelationManager extends RelationManager
{
    protected static string $relationship = 'decisions';

    protected static ?string $title = 'Keputusan';

    public function table(Table $table): Table
    {
        return $table
            ->description('Keputusan Direksi, persetujuan dan nasihat Dewan Komisaris, serta rekomendasi komite dicatat sebagai hasil yang berbeda.')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('agendaItem', 'votes'))
            ->columns([
                TextColumn::make('number')->label('Nomor')->weight('bold'),
                TextColumn::make('kind')->label('Jenis')->badge(),
                TextColumn::make('agendaItem.seq')->label('Agenda'),
                TextColumn::make('text')->label('Rumusan')->wrap()->limit(180),
                TextColumn::make('method')->label('Cara'),
                TextColumn::make('tally')->label('Suara')->state(function (Decision $r) {
                    if ($r->votes->isEmpty()) {
                        return '—';
                    }
                    $t = app(DecisionService::class)->tally($r);

                    return "✓{$t['setuju']} ✗{$t['tidak_setuju']} ○{$t['abstain']}";
                }),
                TextColumn::make('result')->label('Hasil')->badge(),
            ])
            ->recordActions([Action::make('open')->label('Buka')->icon('heroicon-o-arrow-top-right-on-square')->url(fn (Decision $r) => DecisionResource::getUrl('view', ['record' => $r]))]);
    }
}
