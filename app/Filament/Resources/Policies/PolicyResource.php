<?php

namespace App\Filament\Resources\Policies;

use App\Filament\Concerns\UsesAccess;
use App\Filament\Resources\Policies\Pages\ManagePolicies;
use App\Models\Policy;
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

/** Kebijakan & Pedoman */
class PolicyResource extends Resource
{
    use UsesAccess;

    protected static ?string $model = Policy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Tata Kelola';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'kebijakan';

    protected static ?string $pluralModelLabel = 'Kebijakan & Pedoman';

    protected static ?string $slug = 'policies';

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
            Select::make('organ_id')->label('Organ')->options(fn () => \App\Models\Organ::orderBy('sort')->pluck('name', 'id')),
            Select::make('kind')->label('Jenis')->options(['anggaran_dasar' => 'Anggaran dasar', 'board_manual' => 'Board manual', 'piagam' => 'Piagam', 'kebijakan' => 'Kebijakan', 'regulasi' => 'Regulasi'])->required(),
            TextInput::make('title')->label('Judul')->required(),
            TextInput::make('version')->label('Versi')->default('1.0'),
            Select::make('status')->label('Status')->options(['draf' => 'Draf', 'berlaku' => 'Berlaku', 'dicabut' => 'Dicabut'])->default('berlaku'),
            DatePicker::make('effective_date')->label('Berlaku sejak'),
            DatePicker::make('review_due')->label('Jadwal kaji ulang'),
            Select::make('document_id')->label('Dokumen')->searchable()->options(fn () => \App\Models\Document::whereIn('id', Access::readableDocumentIds(static::me()))->pluck('title', 'id')),
            Select::make('data_origin')->label('Asal data')->options(\App\Enums\DataOrigin::class)->default('simulated'),
            Textarea::make('summary')->label('Ringkasan')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Judul')->weight('bold')->searchable()->description(fn (Policy $r) => $r->summary)->wrap(),
                TextColumn::make('kind')->label('Jenis')->badge()->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state))),
                TextColumn::make('version')->label('Versi'),
                TextColumn::make('effective_date')->label('Berlaku')->date('d M Y'),
                TextColumn::make('review_due')->label('Kaji ulang')->date('d M Y')->color(fn (Policy $r) => $r->review_due?->isPast() ? 'danger' : null),
                TextColumn::make('status')->label('Status')->badge(),
            ])
            ->filters([

            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePolicies::route('/')];
    }
}
