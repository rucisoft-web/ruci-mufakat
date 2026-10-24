<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Rsvp: string implements HasColor, HasLabel
{
    case Belum = 'belum';
    case Hadir = 'hadir';
    case TidakHadir = 'tidak_hadir';
    case Ragu = 'ragu';

    public function getLabel(): string
    {
        return match ($this) {
            self::Belum => 'Belum menjawab',
            self::Hadir => 'Akan hadir',
            self::TidakHadir => 'Tidak hadir',
            self::Ragu => 'Belum pasti',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Belum => 'gray',
            self::Hadir => 'success',
            self::TidakHadir => 'danger',
            self::Ragu => 'warning',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->getLabel()])->all();
    }
}
