<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Classification: string implements HasColor, HasLabel
{
    case Internal = 'internal';
    case Rahasia = 'rahasia';
    case SangatRahasia = 'sangat_rahasia';

    public function getLabel(): string
    {
        return match ($this) {
            self::Internal => 'Internal',
            self::Rahasia => 'Rahasia',
            self::SangatRahasia => 'Sangat rahasia',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Internal => 'gray',
            self::Rahasia => 'warning',
            self::SangatRahasia => 'danger',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->getLabel()])->all();
    }
}
