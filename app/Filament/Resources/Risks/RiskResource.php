<?php

namespace App\Filament\Resources\Risks;

use App\Filament\Concerns\UsesAccess;
use App\Filament\Resources\Risks\Pages\ManageRisks;
use App\Models\Risk;
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

/** Register Risiko */
class RiskResource extends Resource
{
    use UsesAccess;

    protected static ?string $model = Risk::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|UnitEnum|null $navigationGroup = 'Pengawasan';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'risiko';

    protected static ?string $pluralModelLabel = 'Register Risiko';

    protected static ?string $slug = 'risks';

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
            TextInput::make('code')->label('Kode')->required(),
            TextInput::make('title')->label('Risiko')->required()->columnSpanFull(),
            Select::make('category')->label('Kategori')->options(['keuangan' => 'Keuangan', 'strategis' => 'Strategis', 'operasional' => 'Operasional', 'kepatuhan' => 'Kepatuhan', 'reputasi' => 'Reputasi', 'teknologi' => 'Teknologi'])->required(),
            Select::make('owner_id')->label('Pemilik risiko')->searchable()->options(fn () => \App\Models\User::where('is_active', true)->orderBy('name')->pluck('name', 'id')),
            Select::make('likelihood')->label('Kemungkinan (1–5)')->options(array_combine(range(1, 5), range(1, 5)))->required(),
            Select::make('impact')->label('Dampak (1–5)')->options(array_combine(range(1, 5), range(1, 5)))->required(),
            Select::make('trend')->label('Tren')->options(['membaik' => 'Membaik', 'tetap' => 'Tetap', 'memburuk' => 'Memburuk']),
            TextInput::make('kri_name')->label('Indikator risiko utama (KRI)'),
            TextInput::make('kri_value')->label('Nilai KRI'),
            TextInput::make('kri_threshold')->label('Batas KRI'),
            Select::make('kri_status')->label('Status KRI')->options(['hijau' => 'Hijau', 'kuning' => 'Kuning', 'merah' => 'Merah']),
            Textarea::make('mitigation')->label('Mitigasi')->columnSpanFull(),
            Select::make('data_origin')->label('Asal data')->options(\App\Enums\DataOrigin::class)->default('simulated'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort(fn (Builder $query) => $query->orderByRaw('likelihood * impact desc'))
            ->modifyQueryUsing(fn (Builder $query) => $query->with('owner'))
            ->columns([
                TextColumn::make('code')->label('Kode')->fontFamily('mono')->size('sm'),
                TextColumn::make('title')->label('Risiko')->weight('bold')->wrap()->description(fn (Risk $r) => $r->mitigation),
                TextColumn::make('owner.name')->label('Pemilik'),
                TextColumn::make('score')->label('Skor')->state(fn (Risk $r) => $r->likelihood * $r->impact)->badge()
                    ->color(fn ($state) => $state >= 15 ? 'danger' : ($state >= 8 ? 'warning' : 'success'))->description(fn (Risk $r) => 'K'.$r->likelihood.' × D'.$r->impact),
                TextColumn::make('kri_value')->label('KRI')->description(fn (Risk $r) => $r->kri_name.' · batas '.$r->kri_threshold)->wrap(),
                TextColumn::make('kri_status')->label('Status KRI')->badge()->color(fn ($state) => ['merah' => 'danger', 'kuning' => 'warning', 'hijau' => 'success'][$state] ?? 'gray'),
                TextColumn::make('trend')->label('Tren'),
                TextColumn::make('data_origin')->label('Asal data')->badge(),
            ])
            ->filters([
                SelectFilter::make('category')->label('Kategori')->options(['keuangan' => 'Keuangan', 'strategis' => 'Strategis', 'operasional' => 'Operasional', 'kepatuhan' => 'Kepatuhan', 'reputasi' => 'Reputasi', 'teknologi' => 'Teknologi']),
                SelectFilter::make('kri_status')->label('Status KRI')->options(['hijau' => 'Hijau', 'kuning' => 'Kuning', 'merah' => 'Merah']),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageRisks::route('/')];
    }
}
