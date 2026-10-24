<?php

namespace App\Filament\Resources\Memberships;

use App\Filament\Concerns\UsesAccess;
use App\Filament\Resources\Memberships\Pages\ManageMemberships;
use App\Models\Membership;
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

/** Anggota Dewan */
class MembershipResource extends Resource
{
    use UsesAccess;

    protected static ?string $model = Membership::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'Tata Kelola';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'keanggotaan';

    protected static ?string $pluralModelLabel = 'Anggota Dewan';

    protected static ?string $slug = 'memberships';

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
            Select::make('organ_id')->label('Organ')->required()->options(fn () => \App\Models\Organ::orderBy('sort')->pluck('name', 'id')),
            Select::make('position')->label('Kedudukan')->options(['ketua' => 'Ketua', 'anggota' => 'Anggota', 'sekretaris' => 'Sekretaris', 'pengamat' => 'Pengamat'])->required(),
            TextInput::make('position_title')->label('Jabatan'),
            Toggle::make('has_vote')->label('Mempunyai hak suara')->default(true),
            Toggle::make('is_independent')->label('Independen'),
            DatePicker::make('term_start')->label('Mulai menjabat'),
            DatePicker::make('term_end')->label('Akhir masa jabatan'),
            TextInput::make('term_number')->label('Periode ke-')->numeric()->default(1),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('organ_id')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user', 'organ'))
            ->recordClasses(fn (Membership $r) => $r->is_active && $r->term_end && $r->term_end->lessThan(now()->addYear()) ? 'bg-amber-50 dark:bg-amber-950/30' : null)
            ->columns([
                TextColumn::make('user.name')->label('Nama')->weight('bold')->searchable()->description(fn (Membership $r) => $r->position_title),
                TextColumn::make('organ.short_name')->label('Organ')->badge()->color('primary'),
                TextColumn::make('position')->label('Kedudukan')->formatStateUsing(fn ($state) => ucfirst($state)),
                IconColumn::make('is_independent')->label('Independen')->boolean(),
                IconColumn::make('has_vote')->label('Hak suara')->boolean(),
                TextColumn::make('term_start')->label('Mulai')->date('d M Y'),
                TextColumn::make('term_end')->label('Berakhir')->date('d M Y')
                    ->color(fn (Membership $r) => $r->term_end && $r->term_end->lessThan(now()->addYear()) ? 'warning' : null)
                    ->description(fn (Membership $r) => $r->term_end && $r->term_end->lessThan(now()->addYear()) ? 'berakhir '.$r->term_end->diffForHumans() : null),
                TextColumn::make('term_number')->label('Periode')->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('organ_id')->label('Organ')->relationship('organ', 'name'),
                Filter::make('ending')->label('Berakhir dalam 12 bulan')->query(fn (Builder $query) => $query->whereDate('term_end', '<', now()->addYear())),
                Filter::make('active')->label('Hanya aktif')->default()->query(fn (Builder $query) => $query->where('is_active', true)),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageMemberships::route('/')];
    }
}
