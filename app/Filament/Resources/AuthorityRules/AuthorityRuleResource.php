<?php

namespace App\Filament\Resources\AuthorityRules;

use App\Filament\Concerns\UsesAccess;
use App\Filament\Resources\AuthorityRules\Pages\ManageAuthorityRules;
use App\Models\AuthorityRule;
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

/** Matriks Kewenangan */
class AuthorityRuleResource extends Resource
{
    use UsesAccess;

    protected static ?string $model = AuthorityRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Tata Kelola';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'kewenangan';

    protected static ?string $pluralModelLabel = 'Matriks Kewenangan';

    protected static ?string $slug = 'authority-rules';

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
            Select::make('company_id')->label('Perusahaan')->options(fn () => \App\Models\Company::pluck('short_name', 'id'))->default(fn () => \App\Models\Company::whereNull('parent_id')->value('id'))->required(),
            TextInput::make('matter')->label('Tindakan')->required()->columnSpanFull(),
            TextInput::make('threshold')->label('Ambang'),
            Select::make('approver')->label('Pemutus')->options(['direksi' => 'Direksi', 'dekom_persetujuan' => 'Persetujuan Dewan Komisaris', 'dekom_nasihat' => 'Nasihat Dewan Komisaris', 'rups' => 'RUPS'])->required(),
            TextInput::make('basis')->label('Dasar')->columnSpanFull(),
            Textarea::make('notes')->label('Catatan')->columnSpanFull(),
            Select::make('data_origin')->label('Asal data')->options(\App\Enums\DataOrigin::class)->default('simulated'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('matter')->label('Tindakan')->weight('bold')->wrap()->searchable(),
                TextColumn::make('threshold')->label('Ambang')->wrap(),
                TextColumn::make('approver')->label('Pemutus')->badge()->formatStateUsing(fn ($state) => ['direksi' => 'Direksi', 'dekom_persetujuan' => 'Persetujuan Dekom', 'dekom_nasihat' => 'Nasihat Dekom', 'rups' => 'RUPS'][$state] ?? $state)
                    ->color(fn ($state) => ['direksi' => 'success', 'dekom_persetujuan' => 'primary', 'dekom_nasihat' => 'info', 'rups' => 'danger'][$state] ?? 'gray'),
                TextColumn::make('basis')->label('Dasar')->wrap()->size('sm')->color('gray'),
                TextColumn::make('data_origin')->label('Asal data')->badge(),
            ])
            ->filters([

            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageAuthorityRules::route('/')];
    }
}
