<?php

namespace App\Support;

/** Format angka gaya Indonesia. */
class Fmt
{
    public static function num(float|int|null $value, int $decimals = 0): string
    {
        if ($value === null) {
            return '—';
        }

        $text = number_format(abs($value), $decimals, ',', '.');

        return $value < 0 ? "({$text})" : $text;
    }

    public static function pct(float|int|null $value, int $decimals = 2): string
    {
        return $value === null ? '—' : number_format($value, $decimals, ',', '.').'%';
    }

    /** Rp juta → teks ringkas (Rp9,22 T / Rp443,4 M). */
    public static function rpJuta(float|int|null $juta): string
    {
        if ($juta === null) {
            return '—';
        }

        $sign = $juta < 0 ? '-' : '';
        $abs = abs($juta);

        return $abs >= 1_000_000
            ? $sign.'Rp'.number_format($abs / 1_000_000, 2, ',', '.').' T'
            : $sign.'Rp'.number_format($abs / 1_000, 1, ',', '.').' M';
    }
}
