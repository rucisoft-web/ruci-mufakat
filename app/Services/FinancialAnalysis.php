<?php

namespace App\Services;

use App\Models\Company;
use App\Models\FinancialFact;
use Illuminate\Support\Collection;

/**
 * Perhitungan rasio dilakukan aplikasi secara deterministik.
 * AI hanya menerima hasil hitung untuk dijelaskan, tidak pernah menghitung sendiri.
 */
class FinancialAnalysis
{
    /** @return Collection<string, FinancialFact> kunci "metric|period" */
    public function facts(Company $company): Collection
    {
        return FinancialFact::where('company_id', $company->id)->with('document')->get()->keyBy(fn ($f) => $f->metric.'|'.$f->period);
    }

    public function value(Collection $facts, string $metric, string $period): ?float
    {
        return $facts->get($metric.'|'.$period)?->value;
    }

    /**
     * @return array<int, array{code: string, label: string, formula: string, unit: string, values: array<string, float|null>, inputs: array<int, string>, good: string}>
     */
    public function ratios(Company $company, array $periods = ['2024', '2025']): array
    {
        $f = $this->facts($company);
        $v = fn (string $m, string $p) => $this->value($f, $m, $p);
        $div = fn (?float $a, ?float $b) => ($a === null || ! $b) ? null : $a / $b;
        $pct = fn (?float $a, ?float $b) => ($r = $div($a, $b)) === null ? null : $r * 100;
        $defs = [
            ['margin_bruto', 'Margin laba bruto', 'Laba bruto ÷ Penjualan neto × 100', '%', fn ($p) => $pct($v('laba_bruto', $p), $v('penjualan', $p)), ['laba_bruto', 'penjualan'], 'naik'],
            ['margin_usaha', 'Margin usaha', 'Laba (rugi) usaha ÷ Penjualan neto × 100', '%', fn ($p) => $pct($v('laba_usaha', $p), $v('penjualan', $p)), ['laba_usaha', 'penjualan'], 'naik'],
            ['margin_bersih', 'Margin bersih', 'Laba (rugi) tahun berjalan ÷ Penjualan neto × 100', '%', fn ($p) => $pct($v('laba_bersih', $p), $v('penjualan', $p)), ['laba_bersih', 'penjualan'], 'naik'],
            ['rasio_lancar', 'Rasio lancar', 'Aset lancar ÷ Liabilitas jangka pendek', 'kali', fn ($p) => $div($v('aset_lancar', $p), $v('liabilitas_pendek', $p)), ['aset_lancar', 'liabilitas_pendek'], 'naik'],
            ['der', 'Liabilitas terhadap ekuitas', '(Total aset − Ekuitas) ÷ Ekuitas', 'kali', fn ($p) => $v('total_aset', $p) === null ? null : $div($v('total_aset', $p) - $v('ekuitas', $p), $v('ekuitas', $p)), ['total_aset', 'ekuitas'], 'turun'],
            ['roa', 'Imbal hasil aset (ROA)', 'Laba (rugi) tahun berjalan ÷ Total aset × 100', '%', fn ($p) => $pct($v('laba_bersih', $p), $v('total_aset', $p)), ['laba_bersih', 'total_aset'], 'naik'],
            ['roe', 'Imbal hasil ekuitas (ROE)', 'Laba (rugi) tahun berjalan ÷ Ekuitas × 100', '%', fn ($p) => $pct($v('laba_bersih', $p), $v('ekuitas', $p)), ['laba_bersih', 'ekuitas'], 'naik'],
            ['kas_operasi_penjualan', 'Arus kas operasi terhadap penjualan', 'Arus kas operasi ÷ Penjualan neto × 100', '%', fn ($p) => $pct($v('arus_kas_operasi', $p), $v('penjualan', $p)), ['arus_kas_operasi', 'penjualan'], 'naik'],
        ];

        return collect($defs)->map(fn ($d) => [
            'code' => $d[0], 'label' => $d[1], 'formula' => $d[2], 'unit' => $d[3],
            'values' => collect($periods)->mapWithKeys(fn ($p) => [$p => $this->safe(fn () => $d[4]($p))])->all(),
            'inputs' => $d[5], 'good' => $d[6],
        ])->all();
    }

    /** Pertumbuhan antarperiode untuk indikator utama. */
    public function growth(Company $company, string $from = '2024', string $to = '2025'): array
    {
        $f = $this->facts($company);
        $out = [];

        foreach (['penjualan', 'laba_bruto', 'laba_usaha', 'laba_bersih', 'aset_lancar', 'liabilitas_pendek', 'total_aset', 'ekuitas', 'arus_kas_operasi', 'kas_akhir'] as $m) {
            $a = $this->value($f, $m, $from);
            $b = $this->value($f, $m, $to);
            $fact = $f->get($m.'|'.$to);

            if ($a === null || $b === null || ! $fact) {
                continue;
            }

            $out[$m] = [
                'label' => $fact->label,
                'from' => $a,
                'to' => $b,
                'change' => $b - $a,
                // Untuk rugi, besaran rugi dibandingkan secara absolut agar "rugi turun 63%" terbaca benar.
                'pct' => ($a < 0 && $b < 0) ? (abs($b) / abs($a) - 1) * 100 : ($a != 0 ? ($b / $a - 1) * 100 : null),
                'is_loss' => $a < 0 && $b < 0,
                'page' => $fact->source_page,
            ];
        }

        return $out;
    }

    /**
     * Pemeriksaan kualitas data: membandingkan hasil hitung dengan angka yang tertulis di sumber.
     *
     * @return array<int, array{metric: string, message: string, source: string, computed: string}>
     */
    public function qualityFlags(Company $company): array
    {
        $flags = [];
        $f = $this->facts($company);

        foreach ($f->where('needs_clarification', true) as $fact) {
            $flags[] = [
                'metric' => $fact->label.' '.$fact->period,
                'message' => $fact->quality_note,
                'source' => number_format($fact->value, 2, ',', '.').' '.$fact->unit.' (hlm. '.$fact->source_page.')',
                'computed' => $this->computedFor($fact->metric, $fact->period, $f),
            ];
        }

        return $flags;
    }

    protected function computedFor(string $metric, string $period, Collection $f): string
    {
        if ($metric === 'rasio_lancar_sumber') {
            $r = $this->value($f, 'aset_lancar', $period) / $this->value($f, 'liabilitas_pendek', $period);

            return number_format($r, 4, ',', '.').' kali = '.number_format($r * 100, 2, ',', '.').'%';
        }

        return '—';
    }

    protected function safe(callable $fn): ?float
    {
        try {
            $r = $fn();

            return is_finite((float) $r) ? round((float) $r, 4) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
