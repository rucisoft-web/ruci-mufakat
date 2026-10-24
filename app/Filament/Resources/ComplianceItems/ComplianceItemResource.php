<?php

namespace App\Filament\Resources\ComplianceItems;

use App\Filament\Concerns\UsesAccess;
use App\Filament\Resources\ComplianceItems\Pages\ManageComplianceItems;
use App\Models\ComplianceItem;
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

/** Kepatuhan & Temuan */
class ComplianceItemResource extends Resource
{
    use UsesAccess;

    protected static ?string $model = ComplianceItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Pengawasan';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'kewajiban/temuan';

    protected static ?string $pluralModelLabel = 'Kepatuhan & Temuan';

    protected static ?string $slug = 'compliance-items';

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
            Select::make('kind')->label('Jenis')->options(['kewajiban' => 'Kewajiban', 'temuan' => 'Temuan audit'])->required(),
            TextInput::make('title')->label('Uraian')->required()->columnSpanFull(),
            TextInput::make('basis')->label('Dasar'),
            Select::make('owner_id')->label('Penanggung jawab')->searchable()->options(fn () => \App\Models\User::where('is_active', true)->orderBy('name')->pluck('name', 'id')),
            DatePicker::make('due_date')->label('Tenggat'),
            Select::make('status')->label('Status')->options(['terbuka' => 'Terbuka', 'dalam_perbaikan' => 'Dalam perbaikan', 'selesai' => 'Selesai', 'terlambat' => 'Terlambat'])->default('terbuka'),
            Select::make('severity')->label('Tingkat')->options(['tinggi' => 'Tinggi', 'sedang' => 'Sedang', 'rendah' => 'Rendah'])->default('sedang'),
            Select::make('data_origin')->label('Asal data')->options(\App\Enums\DataOrigin::class)->default('simulated'),
            Textarea::make('notes')->label('Catatan')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('due_date')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('owner'))
            ->columns([
                TextColumn::make('kind')->label('Jenis')->badge()->color(fn ($state) => $state === 'temuan' ? 'warning' : 'info')->formatStateUsing(fn ($state) => ucfirst($state)),
                TextColumn::make('title')->label('Uraian')->weight('bold')->wrap()->description(fn (ComplianceItem $r) => $r->basis),
                TextColumn::make('owner.name')->label('Penanggung jawab'),
                TextColumn::make('due_date')->label('Tenggat')->date('d M Y')->color(fn (ComplianceItem $r) => $r->status !== 'selesai' && $r->due_date?->isPast() ? 'danger' : null),
                TextColumn::make('severity')->label('Tingkat')->badge()->color(fn ($state) => ['tinggi' => 'danger', 'sedang' => 'warning'][$state] ?? 'gray'),
                TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state)))->color(fn ($state) => ['selesai' => 'success', 'terlambat' => 'danger', 'dalam_perbaikan' => 'warning'][$state] ?? 'gray'),
            ])
            ->filters([
                SelectFilter::make('kind')->label('Jenis')->options(['kewajiban' => 'Kewajiban', 'temuan' => 'Temuan audit']),
                SelectFilter::make('status')->options(['terbuka' => 'Terbuka', 'dalam_perbaikan' => 'Dalam perbaikan', 'selesai' => 'Selesai', 'terlambat' => 'Terlambat']),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageComplianceItems::route('/')];
    }
}
