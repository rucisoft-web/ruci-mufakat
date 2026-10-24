<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasColor, HasLabel
{
    case Admin = 'admin';
    case Sekper = 'sekper';
    case Sekdekom = 'sekdekom';
    case Komisaris = 'komisaris';
    case Direksi = 'direksi';
    case Komite = 'komite';
    case Pic = 'pic';
    case Auditor = 'auditor';
    case Tamu = 'tamu';
    case TamuDemo = 'tamu_demo';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Administrator teknis',
            self::Sekper => 'Sekretaris Perusahaan',
            self::Sekdekom => 'Sekretaris Dewan Komisaris',
            self::Komisaris => 'Komisaris',
            self::Direksi => 'Direksi',
            self::Komite => 'Anggota komite',
            self::Pic => 'PIC manajemen',
            self::Auditor => 'Auditor',
            self::Tamu => 'Tamu/presenter',
            self::TamuDemo => 'Tamu demo (hanya lihat)',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'gray',
            self::Sekper => 'info',
            self::Sekdekom => 'info',
            self::Komisaris => 'primary',
            self::Direksi => 'success',
            self::Komite => 'warning',
            self::Pic => 'gray',
            self::Auditor => 'danger',
            self::Tamu => 'gray',
            self::TamuDemo => 'gray',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->getLabel()])->all();
    }
}
