<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Concerns\UsesAccess;
use App\Filament\Resources\AuditLogs\Pages\ManageAuditLogs;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AuditLogResource extends Resource
{
    use UsesAccess;

    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFingerPrint;

    protected static string|UnitEnum|null $navigationGroup = 'Administrasi';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'catatan audit';

    protected static ?string $pluralModelLabel = 'Jejak Audit';

    protected static ?string $slug = 'audit-logs';

    public static function canViewAny(): bool
    {
        return static::me()->isSecretariat() || in_array(static::me()->role?->value, ['admin', 'auditor', 'tamu_demo'], true);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i:s')->sortable(),
                TextColumn::make('user.name')->label('Pengguna')->placeholder('Sistem')->searchable(),
                TextColumn::make('action')->label('Aksi')->badge()->color(fn ($state) => $state === 'akses_ditolak' ? 'danger' : (str_starts_with($state, 'ai_') ? 'warning' : 'gray')),
                TextColumn::make('description')->label('Keterangan')->wrap()->searchable(),
                TextColumn::make('subject_type')->label('Objek')->formatStateUsing(fn ($state, AuditLog $r) => $state ? class_basename($state).' #'.$r->subject_id : '—')->size('sm')->color('gray'),
                TextColumn::make('ip')->label('IP')->size('sm')->color('gray')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')->label('Aksi')->options(fn () => AuditLog::query()->distinct()->orderBy('action')->pluck('action', 'action')),
                Filter::make('denied')->label('Hanya akses ditolak')->query(fn (Builder $query) => $query->where('action', 'akses_ditolak')),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageAuditLogs::route('/')];
    }
}
