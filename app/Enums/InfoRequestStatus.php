<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InfoRequestStatus: string implements HasColor, HasLabel
{
    case Diajukan = 'diajukan';
    case Dijawab = 'dijawab';
    case Klarifikasi = 'klarifikasi';
    case Diterima = 'diterima';

    public function getLabel(): string
    {
        return match ($this) {
            self::Diajukan => 'Diajukan',
            self::Dijawab => 'Dijawab',
            self::Klarifikasi => 'Perlu klarifikasi',
            self::Diterima => 'Diterima/ditutup',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Diajukan => 'info',
            self::Dijawab => 'warning',
            self::Klarifikasi => 'danger',
            self::Diterima => 'success',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->getLabel()])->all();
    }
}
