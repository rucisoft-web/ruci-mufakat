<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AgendaPurpose: string implements HasColor, HasLabel
{
    case Informasi = 'informasi';
    case Diskusi = 'diskusi';
    case Keputusan = 'keputusan';
    case Persetujuan = 'persetujuan';
    case Nasihat = 'nasihat';

    public function getLabel(): string
    {
        return match ($this) {
            self::Informasi => 'Informasi',
            self::Diskusi => 'Diskusi',
            self::Keputusan => 'Keputusan',
            self::Persetujuan => 'Persetujuan Dekom',
            self::Nasihat => 'Nasihat',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Informasi => 'gray',
            self::Diskusi => 'info',
            self::Keputusan => 'success',
            self::Persetujuan => 'primary',
            self::Nasihat => 'warning',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->getLabel()])->all();
    }
}
