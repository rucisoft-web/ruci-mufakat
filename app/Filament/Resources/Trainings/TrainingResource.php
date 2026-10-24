<?php

namespace App\Filament\Resources\Trainings;

use App\Filament\Concerns\UsesAccess;
use App\Filament\Resources\Trainings\Pages\ManageTrainings;
use App\Models\Training;
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

/** Pengembangan Dewan */
class TrainingResource extends Resource
{
    use UsesAccess;

    protected static ?string $model = Training::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Pengawasan';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'pelatihan';

    protected static ?string $pluralModelLabel = 'Pengembangan Dewan';

    protected static ?string $slug = 'trainings';

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
            TextInput::make('title')->label('Program')->required(),
            TextInput::make('provider')->label('Penyelenggara'),
            Select::make('kind')->label('Jenis')->options(['onboarding' => 'Orientasi', 'pelatihan' => 'Pelatihan', 'seminar' => 'Seminar', 'sertifikasi' => 'Sertifikasi'])->default('pelatihan'),
            DatePicker::make('held_on')->label('Tanggal')->required(),
            TextInput::make('hours')->label('Jam')->numeric(),
            Select::make('data_origin')->label('Asal data')->options(\App\Enums\DataOrigin::class)->default('simulated'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('held_on', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->columns([
                TextColumn::make('held_on')->label('Tanggal')->date('d M Y'),
                TextColumn::make('user.name')->label('Anggota')->searchable(),
                TextColumn::make('title')->label('Program')->weight('bold')->wrap()->description(fn (Training $r) => $r->provider),
                TextColumn::make('kind')->label('Jenis')->badge(),
                TextColumn::make('hours')->label('Jam')->numeric(1)->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->label('Total')),
            ])
            ->filters([

            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageTrainings::route('/')];
    }
}
