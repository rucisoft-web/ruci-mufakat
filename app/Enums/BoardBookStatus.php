<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BoardBookStatus: string implements HasColor, HasLabel
{
    case Draf = 'draf';
    case Terbit = 'terbit';
    case Digantikan = 'digantikan';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draf => 'Draf',
            self::Terbit => 'Terbit',
            self::Digantikan => 'Digantikan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draf => 'gray',
            self::Terbit => 'success',
            self::Digantikan => 'warning',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->getLabel()])->all();
    }
}
