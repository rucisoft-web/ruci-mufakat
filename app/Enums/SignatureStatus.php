<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SignatureStatus: string implements HasColor, HasLabel
{
    case Menunggu = 'menunggu';
    case Ditandatangani = 'ditandatangani';
    case Menolak = 'menolak';

    public function getLabel(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu',
            self::Ditandatangani => 'Ditandatangani',
            self::Menolak => 'Tidak menandatangani',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Menunggu => 'warning',
            self::Ditandatangani => 'success',
            self::Menolak => 'danger',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->getLabel()])->all();
    }
}
