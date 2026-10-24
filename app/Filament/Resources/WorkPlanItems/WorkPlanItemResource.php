<?php

namespace App\Filament\Resources\WorkPlanItems;

use App\Filament\Concerns\UsesAccess;
use App\Filament\Resources\WorkPlanItems\Pages\ManageWorkPlanItems;
use App\Models\WorkPlanItem;
use App\Support\Access;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/** Rencana Kerja Tahunan */
class WorkPlanItemResource extends Resource
{
    use UsesAccess;

    protected static ?string $model = WorkPlanItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static string|UnitEnum|null $navigationGroup = 'Tata Kelola';

    protected static ?int $navigationSort = 8;

    protected static ?string $modelLabel = 'rencana kerja';

    protected static ?string $pluralModelLabel = 'Rencana Kerja Tahunan';

    protected static ?string $slug = 'work-plan-items';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('organ_id', Access::organIds(static::me()))->with('organ');
    }

    public static function canViewAny(): bool
    {
        return ! static::me()->role?->value || static::me()->role->value !== 'admin';
    }

    public static function canCreate(): bool
    {
        return static::writable() && (static::me()->isSecretariat());
    }

    public static function canEdit(Model $record): bool
    {
        return static::writable() && (static::me()->isSecretariat());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('organ_id')->label('Organ')->required()->options(fn () => \App\Models\Organ::orderBy('sort')->pluck('name', 'id')),
            TextInput::make('year')->label('Tahun')->numeric()->default(now()->year)->required(),
            Select::make('month')->label('Bulan')->options(collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => \Illuminate\Support\Carbon::create(2026, $m)->translatedFormat('F')]))->required(),
            Select::make('kind')->label('Jenis')->options(['rapat' => 'Rapat', 'program_pengawasan' => 'Program pengawasan', 'kunjungan_kerja' => 'Kunjungan kerja', 'pelatihan' => 'Pelatihan', 'laporan' => 'Laporan'])->required(),
            TextInput::make('title')->label('Kegiatan')->required()->columnSpanFull(),
            Select::make('status')->label('Status')->options(['direncanakan' => 'Direncanakan', 'terlaksana' => 'Terlaksana', 'ditunda' => 'Ditunda', 'batal' => 'Batal'])->default('direncanakan'),
            Textarea::make('notes')->label('Catatan')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort(fn (Builder $query) => $query->orderBy('year')->orderBy('month'))
            ->columns([
                TextColumn::make('month')->label('Bulan')->formatStateUsing(fn ($state, WorkPlanItem $r) => \Illuminate\Support\Carbon::create($r->year, $state)->translatedFormat('M Y')),
                TextColumn::make('organ.short_name')->label('Organ')->badge()->color('primary'),
                TextColumn::make('title')->label('Kegiatan')->weight('bold')->wrap()->searchable(),
                TextColumn::make('kind')->label('Jenis')->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state))),
                TextColumn::make('status')->label('Status')->badge()->color(fn ($state) => ['terlaksana' => 'success', 'ditunda' => 'warning', 'batal' => 'danger'][$state] ?? 'gray'),
            ])
            ->filters([
                SelectFilter::make('organ_id')->label('Organ')->relationship('organ', 'name'),
                SelectFilter::make('status')->options(['direncanakan' => 'Direncanakan', 'terlaksana' => 'Terlaksana', 'ditunda' => 'Ditunda', 'batal' => 'Batal']),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageWorkPlanItems::route('/')];
    }
}
