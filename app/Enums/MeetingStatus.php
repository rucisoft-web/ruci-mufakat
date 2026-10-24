<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MeetingStatus: string implements HasColor, HasLabel
{
    case Draf = 'draf';
    case Dijadwalkan = 'dijadwalkan';
    case BahanTerbit = 'bahan_terbit';
    case Berlangsung = 'berlangsung';
    case Selesai = 'selesai';
    case GagalKuorum = 'gagal_kuorum';
    case Dibatalkan = 'dibatalkan';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draf => 'Draf',
            self::Dijadwalkan => 'Dijadwalkan',
            self::BahanTerbit => 'Bahan terbit',
            self::Berlangsung => 'Berlangsung',
            self::Selesai => 'Selesai',
            self::GagalKuorum => 'Gagal kuorum',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draf => 'gray',
            self::Dijadwalkan => 'info',
            self::BahanTerbit => 'primary',
            self::Berlangsung => 'warning',
            self::Selesai => 'success',
            self::GagalKuorum => 'danger',
            self::Dibatalkan => 'gray',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->getLabel()])->all();
    }
}
