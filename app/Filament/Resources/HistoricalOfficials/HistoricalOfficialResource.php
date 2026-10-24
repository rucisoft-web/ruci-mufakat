<?php

namespace App\Filament\Resources\HistoricalOfficials;

use App\Filament\Concerns\UsesAccess;
use App\Filament\Resources\HistoricalOfficials\Pages\ManageHistoricalOfficials;
use App\Models\HistoricalOfficial;
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

/** Direktori Historis */
class HistoricalOfficialResource extends Resource
{
    use UsesAccess;

    protected static ?string $model = HistoricalOfficial::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookmarkSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Tata Kelola';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'pejabat historis';

    protected static ?string $pluralModelLabel = 'Direktori Historis';

    protected static ?string $slug = 'historical-officials';

    public static function canViewAny(): bool
    {
        return true;
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
            Select::make('company_id')->label('Perusahaan')->options(fn () => \App\Models\Company::pluck('short_name', 'id'))->default(fn () => \App\Models\Company::whereNull('parent_id')->value('id'))->required(),
            TextInput::make('name')->label('Nama')->required(),
            Select::make('organ')->label('Organ')->options(['dekom' => 'Dewan Komisaris', 'direksi' => 'Direksi'])->required(),
            TextInput::make('position')->label('Jabatan')->required(),
            DatePicker::make('as_of')->label('Per tanggal')->required(),
            TextInput::make('appointment_basis')->label('Dasar pengangkatan'),
            TextInput::make('source_ref')->label('Sumber')->columnSpanFull(),
            Select::make('data_origin')->label('Asal data')->options(\App\Enums\DataOrigin::class)->default('simulated'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('name')->label('Nama')->weight('bold')->searchable(),
                TextColumn::make('position')->label('Jabatan'),
                TextColumn::make('organ')->label('Organ')->badge()->formatStateUsing(fn ($state) => $state === 'dekom' ? 'Dewan Komisaris' : 'Direksi'),
                TextColumn::make('as_of')->label('Per')->date('d M Y'),
                TextColumn::make('source_ref')->label('Sumber')->wrap()->size('sm')->color('gray'),
                TextColumn::make('data_origin')->label('Asal data')->badge(),
            ])
            ->filters([

            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageHistoricalOfficials::route('/')];
    }
}
