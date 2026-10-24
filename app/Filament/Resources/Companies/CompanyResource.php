<?php

namespace App\Filament\Resources\Companies;

use App\Filament\Concerns\UsesAccess;
use App\Filament\Resources\Companies\Pages\ManageCompanies;
use App\Models\Company;
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

/** Grup Perusahaan */
class CompanyResource extends Resource
{
    use UsesAccess;

    protected static ?string $model = Company::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Tata Kelola';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'perusahaan';

    protected static ?string $pluralModelLabel = 'Grup Perusahaan';

    protected static ?string $slug = 'companies';

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function canCreate(): bool
    {
        return static::writable() && (static::me()->isSecretariat() || static::me()->role?->value === 'admin');
    }

    public static function canEdit(Model $record): bool
    {
        return static::writable() && (static::me()->isSecretariat() || static::me()->role?->value === 'admin');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('parent_id')->label('Induk')->options(fn () => Company::pluck('short_name', 'id')),
            TextInput::make('name')->label('Nama')->required(),
            TextInput::make('short_name')->label('Nama singkat')->required(),
            TextInput::make('ticker')->label('Kode saham'),
            Select::make('relation')->label('Hubungan')->options(['induk' => 'Induk', 'anak' => 'Entitas anak', 'cucu' => 'Entitas cucu', 'afiliasi' => 'Afiliasi'])->default('anak'),
            TextInput::make('ownership_pct')->label('Kepemilikan (%)')->numeric(),
            TextInput::make('sector')->label('Sektor'),
            Select::make('data_origin')->label('Asal data')->options(\App\Enums\DataOrigin::class)->default('simulated'),
            Textarea::make('description')->label('Keterangan')->columnSpanFull(),
            TextInput::make('source_ref')->label('Rujukan sumber')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Perusahaan')->weight('bold')->description(fn (Company $r) => $r->description)->wrap(),
                TextColumn::make('ticker')->label('Kode'),
                TextColumn::make('relation')->label('Hubungan')->badge()->formatStateUsing(fn ($state) => ucfirst($state)),
                TextColumn::make('ownership_pct')->label('Kepemilikan')->suffix('%')->placeholder('—'),
                TextColumn::make('sector')->label('Sektor'),
                TextColumn::make('data_origin')->label('Asal data')->badge(),
            ])
            ->filters([

            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCompanies::route('/')];
    }
}
