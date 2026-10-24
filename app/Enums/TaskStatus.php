<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TaskStatus: string implements HasColor, HasLabel
{
    case BelumMulai = 'belum_mulai';
    case Berjalan = 'berjalan';
    case Diajukan = 'diajukan';
    case Selesai = 'selesai';
    case DibukaKembali = 'dibuka_kembali';

    public function getLabel(): string
    {
        return match ($this) {
            self::BelumMulai => 'Belum mulai',
            self::Berjalan => 'Berjalan',
            self::Diajukan => 'Diajukan untuk verifikasi',
            self::Selesai => 'Selesai terverifikasi',
            self::DibukaKembali => 'Dibuka kembali',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::BelumMulai => 'gray',
            self::Berjalan => 'info',
            self::Diajukan => 'warning',
            self::Selesai => 'success',
            self::DibukaKembali => 'danger',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->getLabel()])->all();
    }
}
