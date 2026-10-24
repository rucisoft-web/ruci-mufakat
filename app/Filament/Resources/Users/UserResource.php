<?php

namespace App\Filament\Resources\Users;

use App\Filament\Concerns\UsesAccess;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use App\Support\Access;
use BackedEnum;
use App\Support\Audit;
use Filament\Actions\Action;
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

/** Pengguna */
class UserResource extends Resource
{
    use UsesAccess;

    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Administrasi';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'pengguna';

    protected static ?string $pluralModelLabel = 'Pengguna';

    protected static ?string $slug = 'users';

    public static function canViewAny(): bool
    {
        return static::me()->isSecretariat() || static::me()->role?->value === 'admin';
    }

    public static function canCreate(): bool
    {
        return static::writable() && (static::me()->role?->value === 'admin');
    }

    public static function canEdit(Model $record): bool
    {
        return static::writable() && (static::me()->role?->value === 'admin');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Nama')->required(),
            TextInput::make('email')->label('Email')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('title')->label('Jabatan'),
            Select::make('role')->label('Peran')->options(\App\Enums\UserRole::class)->required(),
            TextInput::make('phone')->label('Telepon'),
            TextInput::make('password')->label('Kata sandi')->password()->revealable()->dehydrated(fn ($state) => filled($state))->required(fn (string $operation) => $operation === 'create')->minLength(10),
            DateTimePicker::make('access_until')->label('Akses berakhir (tamu/presenter)')->seconds(false),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Nama')->weight('bold')->searchable()->description(fn (User $r) => $r->title),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('role')->label('Peran')->badge(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('access_until')->label('Akses s.d.')->dateTime('d M Y H:i')->placeholder('—'),
                IconColumn::make('mfa')->label('MFA')->state(fn (User $r) => filled($r->app_authentication_secret))->boolean(),
            ])
            ->filters([
                SelectFilter::make('role')->label('Peran')->options(\App\Enums\UserRole::class),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('resetMfa')->label('Reset MFA')->icon('heroicon-o-key')->color('warning')
                    ->visible(fn (User $r) => filled($r->app_authentication_secret) && ! static::me()->isReadOnly())
                    ->requiresConfirmation()
                    ->modalDescription('Pengguna akan diminta memindai kode QR baru saat masuk berikutnya.')
                    ->action(function (User $r) {
                        $r->forceFill(['app_authentication_secret' => null, 'app_authentication_recovery_codes' => null])->save();
                        Audit::log('reset_mfa', 'MFA '.$r->name.' direset', $r);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageUsers::route('/')];
    }
}
