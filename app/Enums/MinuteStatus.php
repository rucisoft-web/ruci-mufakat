<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MinuteStatus: string implements HasColor, HasLabel
{
    case Draf = 'draf';
    case Reviu = 'reviu';
    case EdarTtd = 'edar_ttd';
    case Final = 'final';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draf => 'Draf',
            self::Reviu => 'Reviu peserta',
            self::EdarTtd => 'Edaran tanda tangan',
            self::Final => 'Final',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draf => 'gray',
            self::Reviu => 'info',
            self::EdarTtd => 'warning',
            self::Final => 'success',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->getLabel()])->all();
    }
}
