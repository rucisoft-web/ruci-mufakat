<?php

namespace App\Filament\Resources\Organs;

use App\Filament\Concerns\UsesAccess;
use App\Filament\Resources\Organs\Pages\ManageOrgans;
use App\Models\Organ;
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

/** Organ & Komite */
class OrganResource extends Resource
{
    use UsesAccess;

    protected static ?string $model = Organ::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Tata Kelola';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'organ';

    protected static ?string $pluralModelLabel = 'Organ & Komite';

    protected static ?string $slug = 'organs';

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
            Select::make('company_id')->label('Perusahaan')->options(fn () => \App\Models\Company::pluck('short_name', 'id'))->default(fn () => \App\Models\Company::whereNull('parent_id')->value('id'))->required(),
            Select::make('parent_organ_id')->label('Di bawah organ')->options(fn () => Organ::orderBy('sort')->pluck('name', 'id')),
            Select::make('kind')->label('Jenis')->options(\App\Enums\OrganKind::class)->required(),
            TextInput::make('name')->label('Nama')->required(),
            TextInput::make('short_name')->label('Nama singkat')->required(),
            TextInput::make('color')->label('Warna')->default('#1d4ed8'),
            TextInput::make('quorum_numerator')->label('Kuorum: pembilang')->numeric()->default(1)->required(),
            TextInput::make('quorum_denominator')->label('Kuorum: penyebut')->numeric()->default(2)->required(),
            Toggle::make('quorum_strictly_more')->label('Harus LEBIH dari pecahan (mis. lebih dari 1/2)')->default(true),
            TextInput::make('min_meetings_per_year')->label('Rapat minimum per tahun (internal)')->numeric(),
            TextInput::make('material_days_before')->label('Bahan paling lambat (hari sebelum rapat)')->numeric()->default(5),
            TextInput::make('sort')->label('Urutan')->numeric(),
            Textarea::make('mandate')->label('Mandat')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['memberships' => fn ($m) => $m->where('is_active', true)]))
            ->columns([
                TextColumn::make('name')->label('Organ')->weight('bold')->description(fn (Organ $r) => $r->mandate)->wrap(),
                TextColumn::make('kind')->label('Jenis')->badge(),
                TextColumn::make('memberships_count')->label('Anggota aktif')->alignCenter(),
                TextColumn::make('quorum')->label('Kuorum')->state(fn (Organ $r) => ($r->quorum_strictly_more ? '> ' : '≥ ').$r->quorum_numerator.'/'.$r->quorum_denominator.' anggota'),
                TextColumn::make('min_meetings_per_year')->label('Rapat min./thn')->alignCenter(),
                TextColumn::make('material_days_before')->label('Bahan H-')->alignCenter(),
            ])
            ->filters([

            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageOrgans::route('/')];
    }
}
