<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DecisionKind: string implements HasColor, HasLabel
{
    case KeputusanDireksi = 'keputusan_direksi';
    case PersetujuanDekom = 'persetujuan_dekom';
    case NasihatDekom = 'nasihat_dekom';
    case RekomendasiKomite = 'rekomendasi_komite';

    public function getLabel(): string
    {
        return match ($this) {
            self::KeputusanDireksi => 'Keputusan Direksi',
            self::PersetujuanDekom => 'Persetujuan Dewan Komisaris',
            self::NasihatDekom => 'Nasihat Dewan Komisaris',
            self::RekomendasiKomite => 'Rekomendasi Komite',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::KeputusanDireksi => 'success',
            self::PersetujuanDekom => 'primary',
            self::NasihatDekom => 'info',
            self::RekomendasiKomite => 'warning',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->getLabel()])->all();
    }
}
