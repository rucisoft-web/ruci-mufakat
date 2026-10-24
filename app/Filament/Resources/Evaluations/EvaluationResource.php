<?php

namespace App\Filament\Resources\Evaluations;

use App\Filament\Concerns\UsesAccess;
use App\Filament\Resources\Evaluations\Pages\ManageEvaluations;
use App\Models\Evaluation;
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

/** Evaluasi Dewan */
class EvaluationResource extends Resource
{
    use UsesAccess;

    protected static ?string $model = Evaluation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|UnitEnum|null $navigationGroup = 'Pengawasan';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'evaluasi';

    protected static ?string $pluralModelLabel = 'Evaluasi Dewan';

    protected static ?string $slug = 'evaluations';

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
            Select::make('kind')->label('Jenis')->options(['penilaian_mandiri' => 'Penilaian mandiri', 'penilaian_komite' => 'Penilaian komite', 'survei_rapat' => 'Survei efektivitas rapat', 'kompetensi' => 'Pemetaan kompetensi'])->required(),
            TextInput::make('title')->label('Judul')->required(),
            Select::make('status')->label('Status')->options(['berjalan' => 'Berjalan', 'selesai' => 'Selesai'])->default('berjalan'),
            TextInput::make('invited')->label('Responden diundang')->numeric(),
            TextInput::make('respondents')->label('Responden menjawab')->numeric(),
            KeyValue::make('scores')->label('Skor per aspek (1–5)')->keyLabel('Aspek')->valueLabel('Skor')->columnSpanFull(),
            Textarea::make('summary')->label('Ringkasan')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Evaluasi')->weight('bold')->wrap()->description(fn (Evaluation $r) => $r->summary),
                TextColumn::make('organ.short_name')->label('Organ')->badge()->color('primary'),
                TextColumn::make('year')->label('Tahun'),
                TextColumn::make('response')->label('Respons')->state(fn (Evaluation $r) => $r->respondents.'/'.$r->invited),
                TextColumn::make('avg')->label('Rata-rata skor')->state(fn (Evaluation $r) => $r->scores ? number_format(collect($r->scores)->avg(), 2, ',', '.') : '—')
                    ->description(fn (Evaluation $r) => $r->scores ? 'terendah: '.collect($r->scores)->sort()->keys()->first() : null),
                TextColumn::make('status')->label('Status')->badge()->color(fn ($state) => $state === 'selesai' ? 'success' : 'warning'),
            ])
            ->filters([

            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageEvaluations::route('/')];
    }
}
