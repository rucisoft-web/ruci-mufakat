<?php

namespace App\Filament\Resources\Declarations;

use App\Filament\Concerns\UsesAccess;
use App\Filament\Resources\Declarations\Pages\ManageDeclarations;
use App\Models\Declaration;
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

/** Pernyataan Tahunan */
class DeclarationResource extends Resource
{
    use UsesAccess;

    protected static ?string $model = Declaration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Tata Kelola';

    protected static ?int $navigationSort = 9;

    protected static ?string $modelLabel = 'pernyataan';

    protected static ?string $pluralModelLabel = 'Pernyataan Tahunan';

    protected static ?string $slug = 'declarations';

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
            Select::make('user_id')->label('Anggota')->searchable()->options(fn () => \App\Models\User::where('is_active', true)->orderBy('name')->pluck('name', 'id'))->required(),
            TextInput::make('year')->label('Tahun')->numeric()->default(now()->year)->required(),
            Select::make('kind')->label('Jenis')->options(['independensi' => 'Independensi', 'benturan_kepentingan_tahunan' => 'Benturan kepentingan tahunan', 'kepemilikan_saham' => 'Kepemilikan saham', 'rangkap_jabatan' => 'Rangkap jabatan'])->required(),
            Select::make('status')->label('Status')->options(['menunggu' => 'Menunggu', 'disampaikan' => 'Disampaikan'])->default('menunggu'),
            DateTimePicker::make('submitted_at')->label('Disampaikan pada'),
            Textarea::make('content')->label('Isi')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->columns([
                TextColumn::make('user.name')->label('Anggota')->searchable(),
                TextColumn::make('kind')->label('Jenis')->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state))),
                TextColumn::make('year')->label('Tahun'),
                TextColumn::make('status')->label('Status')->badge()->color(fn ($state) => $state === 'disampaikan' ? 'success' : 'warning'),
                TextColumn::make('submitted_at')->label('Disampaikan')->dateTime('d M Y')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->options(['menunggu' => 'Menunggu', 'disampaikan' => 'Disampaikan']),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageDeclarations::route('/')];
    }
}
