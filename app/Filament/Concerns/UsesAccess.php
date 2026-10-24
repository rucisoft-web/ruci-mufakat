<?php

namespace App\Filament\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Bantuan hak akses bersama untuk resource Filament. */
trait UsesAccess
{
    protected static function me(): User
    {
        return auth()->user();
    }

    /** Akun hanya-lihat (tamu demo, auditor) tidak pernah melihat tombol ubah. */
    protected static function writable(): bool
    {
        return ! static::me()->isReadOnly();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
