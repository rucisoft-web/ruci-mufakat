<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CircularStatus: string implements HasColor, HasLabel
{
    case Draf = 'draf';
    case Beredar = 'beredar';
    case Disetujui = 'disetujui';
    case Ditolak = 'ditolak';
    case Kedaluwarsa = 'kedaluwarsa';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draf => 'Draf',
            self::Beredar => 'Beredar',
            self::Disetujui => 'Disetujui',
            self::Ditolak => 'Ditolak',
            self::Kedaluwarsa => 'Kedaluwarsa',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draf => 'gray',
            self::Beredar => 'info',
            self::Disetujui => 'success',
            self::Ditolak => 'danger',
            self::Kedaluwarsa => 'warning',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->getLabel()])->all();
    }
}
