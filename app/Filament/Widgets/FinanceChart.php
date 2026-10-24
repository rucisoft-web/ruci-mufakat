<?php

namespace App\Filament\Widgets;

use App\Services\FinancialAnalysis;

class FinanceChart extends MufakatChart
{
    protected ?string $heading = 'Kinerja 2025 dibanding 2024';

    protected ?string $description = 'Rp miliar · Fakta laporan tahunan 2025 hlm. 16 dan 18';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $company = $this->company();
        $f = $company ? app(FinancialAnalysis::class)->facts($company) : collect();
        $metrics = ['penjualan' => 'Penjualan', 'laba_bruto' => 'Laba bruto', 'laba_usaha' => 'Laba (rugi) usaha', 'laba_bersih' => 'Laba (rugi) bersih', 'arus_kas_operasi' => 'Kas operasi'];
        $val = fn ($m, $p) => round(($f->get($m.'|'.$p)?->value ?? 0) / 1000, 1);

        return [
            'labels' => array_values($metrics),
            'datasets' => [
                ['label' => '2024', 'data' => array_map(fn ($m) => $val($m, '2024'), array_keys($metrics)), 'backgroundColor' => self::SLATE, 'borderRadius' => 4],
                ['label' => '2025', 'data' => array_map(fn ($m) => $val($m, '2025'), array_keys($metrics)), 'backgroundColor' => self::INK, 'borderRadius' => 4],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return $this->legendBottom();
    }
}
