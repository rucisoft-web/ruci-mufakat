<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrganKind: string implements HasColor, HasLabel
{
    case Direksi = 'direksi';
    case Dekom = 'dekom';
    case Komite = 'komite';
    case Gabungan = 'gabungan';

    public function getLabel(): string
    {
        return match ($this) {
            self::Direksi => 'Direksi',
            self::Dekom => 'Dewan Komisaris',
            self::Komite => 'Komite',
            self::Gabungan => 'Rapat Gabungan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Direksi => 'success',
            self::Dekom => 'primary',
            self::Komite => 'warning',
            self::Gabungan => 'info',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->getLabel()])->all();
    }
}
