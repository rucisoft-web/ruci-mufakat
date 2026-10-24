<?php

namespace App\Filament\Resources\Decisions;

use App\Enums\DecisionKind;
use App\Enums\DecisionResult;
use App\Filament\Concerns\UsesAccess;
use App\Filament\Resources\Decisions\Pages\ListDecisions;
use App\Filament\Resources\Decisions\Pages\ViewDecision;
use App\Models\Decision;
use App\Models\Organ;
use App\Support\Access;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class DecisionResource extends Resource
{
    use UsesAccess;

    protected static ?string $model = Decision::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|UnitEnum|null $navigationGroup = 'Keputusan & Tindak Lanjut';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'keputusan';

    protected static ?string $pluralModelLabel = 'Register Keputusan';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('organ_id', Access::decisionOrganIds(static::me()))->with('organ');
    }

    public static function canCreate(): bool
    {
        return false; // keputusan dicatat dari agenda rapat atau sirkuler
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(4)->columnSpanFull()->schema([
                TextEntry::make('number')->label('Nomor')->weight('bold'),
                TextEntry::make('organ.name')->label('Organ')->badge()->color('primary'),
                TextEntry::make('kind')->label('Jenis')->badge(),
                TextEntry::make('result')->label('Hasil')->badge(),
                TextEntry::make('title')->label('Perihal')->columnSpan(2)->weight('bold'),
                TextEntry::make('decided_on')->label('Tanggal')->date('d F Y'),
                TextEntry::make('method')->label('Cara')->formatStateUsing(fn ($state) => ['musyawarah' => 'Musyawarah mufakat', 'voting' => 'Pemungutan suara', 'sirkuler' => 'Keputusan sirkuler'][$state] ?? $state),
                TextEntry::make('text')->label('Bunyi keputusan')->columnSpanFull()->markdown(),
                TextEntry::make('conditions')->label('Syarat')->columnSpanFull()->visible(fn (Decision $r) => filled($r->conditions)),
                TextEntry::make('basis')->label('Dasar kewenangan')->columnSpan(2)->placeholder('—'),
                TextEntry::make('data_origin')->label('Asal data')->badge(),
                TextEntry::make('status')->label('Status')->formatStateUsing(fn ($state) => ucfirst($state)),
            ]),
            ViewEntry::make('chain')->view('filament.decisions.chain')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('decided_on', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['tasks', 'votes as dissent_count' => fn ($v) => $v->where('vote', '!=', 'setuju')]))
            ->columns([
                TextColumn::make('decided_on')->label('Tanggal')->date('d M Y')->sortable(),
                TextColumn::make('number')->label('Nomor')->searchable()->fontFamily('mono')->size('sm'),
                TextColumn::make('organ.short_name')->label('Organ')->badge()->color('primary'),
                TextColumn::make('title')->label('Perihal')->searchable()->wrap()->description(fn (Decision $r) => $r->kind->getLabel()),
                TextColumn::make('result')->label('Hasil')->badge(),
                TextColumn::make('method')->label('Cara')->formatStateUsing(fn ($state) => ucfirst($state)),
                TextColumn::make('dissent_count')->label('Beda pendapat')->alignCenter()->formatStateUsing(fn ($state) => $state ?: '—')->color(fn ($state) => $state ? 'warning' : null),
                TextColumn::make('tasks_count')->label('Tugas')->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('organ_id')->label('Organ')->options(fn () => Organ::whereIn('id', Access::organIds(static::me()))->orderBy('sort')->pluck('name', 'id')),
                SelectFilter::make('kind')->label('Jenis')->options(DecisionKind::class),
                SelectFilter::make('result')->label('Hasil')->options(DecisionResult::class),
                SelectFilter::make('method')->label('Cara')->options(['musyawarah' => 'Musyawarah', 'voting' => 'Voting', 'sirkuler' => 'Sirkuler']),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDecisions::route('/'),
            'view' => ViewDecision::route('/{record}'),
        ];
    }
}
