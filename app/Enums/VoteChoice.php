<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum VoteChoice: string implements HasColor, HasLabel
{
    case Setuju = 'setuju';
    case TidakSetuju = 'tidak_setuju';
    case Abstain = 'abstain';

    public function getLabel(): string
    {
        return match ($this) {
            self::Setuju => 'Setuju',
            self::TidakSetuju => 'Tidak setuju',
            self::Abstain => 'Abstain',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Setuju => 'success',
            self::TidakSetuju => 'danger',
            self::Abstain => 'gray',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->getLabel()])->all();
    }
}
