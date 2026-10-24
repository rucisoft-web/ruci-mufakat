<?php

namespace App\Models;

use App\Enums\OrganKind;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use SensitiveParameter;

class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** Akun tamu bersama untuk melihat data demo; hanya lihat. */
    public const DEMO_GUEST_EMAIL = 'tamu@mufakat.demo';

    protected $guarded = ['id'];

    protected $hidden = ['password', 'remember_token', 'app_authentication_secret', 'app_authentication_recovery_codes'];

    protected ?Collection $membershipCache = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'is_demo_alias' => 'boolean',
            'access_until' => 'datetime',
            'app_authentication_secret' => 'encrypted',
            'app_authentication_recovery_codes' => 'encrypted:array',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function boardProfile(): HasOne
    {
        return $this->hasOne(BoardProfile::class);
    }

    public function trainings(): HasMany
    {
        return $this->hasMany(Training::class);
    }

    public function declarations(): HasMany
    {
        return $this->hasMany(Declaration::class);
    }

    /** Keanggotaan organ yang berlaku hari ini. */
    public function activeMemberships(): Collection
    {
        return $this->membershipCache ??= $this->memberships()
            ->with('organ')
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('term_end')->orWhereDate('term_end', '>=', today()))
            ->get();
    }

    public function forgetMembershipCache(): void
    {
        $this->membershipCache = null;
    }

    public function isMemberOf(Organ|int $organ): bool
    {
        $id = $organ instanceof Organ ? $organ->id : $organ;

        return $this->activeMemberships()->contains('organ_id', $id);
    }

    public function isMemberOfKind(OrganKind $kind): bool
    {
        return $this->activeMemberships()->contains(fn (Membership $m) => $m->organ->kind === $kind);
    }

    public function isCommissioner(): bool
    {
        return $this->isMemberOfKind(OrganKind::Dekom);
    }

    public function isDirector(): bool
    {
        return $this->isMemberOfKind(OrganKind::Direksi);
    }

    public function isDemoGuest(): bool
    {
        return $this->role === UserRole::TamuDemo;
    }

    /** Akun hanya-lihat tidak boleh mengubah data apa pun. */
    public function isReadOnly(): bool
    {
        return $this->isDemoGuest() || $this->role === UserRole::Auditor;
    }

    public function isSecretariat(): bool
    {
        return in_array($this->role, [UserRole::Sekper, UserRole::Sekdekom], true);
    }

    public function initials(): string
    {
        return collect(explode(' ', preg_replace('/^Demo\s+/', '', $this->name)))
            ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && ($this->access_until === null || $this->access_until->isFuture());
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return null;
    }

    public function getAppAuthenticationSecret(): ?string
    {
        return $this->app_authentication_secret;
    }

    public function saveAppAuthenticationSecret(#[SensitiveParameter] ?string $secret): void
    {
        $this->app_authentication_secret = $secret;
        $this->save();
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }

    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        return $this->app_authentication_recovery_codes;
    }

    public function saveAppAuthenticationRecoveryCodes(#[SensitiveParameter] ?array $codes): void
    {
        $this->app_authentication_recovery_codes = $codes;
        $this->save();
    }
}
