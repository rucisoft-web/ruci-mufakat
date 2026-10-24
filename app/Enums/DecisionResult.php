<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DecisionResult: string implements HasColor, HasLabel
{
    case Disetujui = 'disetujui';
    case DisetujuiBersyarat = 'disetujui_bersyarat';
    case Ditolak = 'ditolak';
    case Ditunda = 'ditunda';
    case Disampaikan = 'disampaikan';

    public function getLabel(): string
    {
        return match ($this) {
            self::Disetujui => 'Disetujui',
            self::DisetujuiBersyarat => 'Disetujui bersyarat',
            self::Ditolak => 'Ditolak',
            self::Ditunda => 'Ditunda',
            self::Disampaikan => 'Disampaikan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Disetujui => 'success',
            self::DisetujuiBersyarat => 'info',
            self::Ditolak => 'danger',
            self::Ditunda => 'warning',
            self::Disampaikan => 'primary',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->getLabel()])->all();
    }
}
