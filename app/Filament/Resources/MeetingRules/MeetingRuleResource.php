<?php

namespace App\Filament\Resources\MeetingRules;

use App\Filament\Concerns\UsesAccess;
use App\Filament\Resources\MeetingRules\Pages\ManageMeetingRules;
use App\Models\MeetingRule;
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

/** Aturan Rapat (POJK) */
class MeetingRuleResource extends Resource
{
    use UsesAccess;

    protected static ?string $model = MeetingRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|UnitEnum|null $navigationGroup = 'Tata Kelola';

    protected static ?int $navigationSort = 7;

    protected static ?string $modelLabel = 'aturan rapat';

    protected static ?string $pluralModelLabel = 'Aturan Rapat (POJK)';

    protected static ?string $slug = 'meeting-rules';

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
            TextInput::make('code')->label('Kode')->required(),
            Select::make('organ_kind')->label('Berlaku untuk')->options(['direksi' => 'Direksi', 'dekom' => 'Dewan Komisaris', 'gabungan' => 'Rapat gabungan', 'komite' => 'Komite', 'semua' => 'Semua'])->required(),
            TextInput::make('title')->label('Aturan')->required()->columnSpanFull(),
            Select::make('metric')->label('Ukuran')->options(['min_meetings_year' => 'Rapat minimum per tahun', 'material_days' => 'Bahan paling lambat (hari)', 'attendance_pct' => 'Kehadiran minimum (%)'])->required(),
            TextInput::make('regulatory_value')->label('Nilai regulasi')->numeric()->required(),
            TextInput::make('internal_value')->label('Nilai kebijakan internal')->numeric(),
            Textarea::make('basis')->label('Dasar')->required()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Kode')->fontFamily('mono')->size('sm'),
                TextColumn::make('title')->label('Aturan')->weight('bold')->wrap(),
                TextColumn::make('regulatory_value')->label('Regulasi')->numeric(),
                TextColumn::make('internal_value')->label('Internal')->numeric(),
                TextColumn::make('basis')->label('Dasar')->wrap()->size('sm')->color('gray'),
            ])
            ->filters([

            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageMeetingRules::route('/')];
    }
}
