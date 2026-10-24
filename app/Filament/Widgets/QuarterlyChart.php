<?php

namespace App\Filament\Widgets;

use App\Models\FinancialFact;

class QuarterlyChart extends MufakatChart
{
    protected ?string $heading = 'Triwulanan 2026';

    protected ?string $description = 'Rp miliar per triwulan · Simulasi';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $company = $this->company();
        $rows = FinancialFact::where('company_id', $company?->id)->where('period_type', 'kuartal')->orderBy('period')->get();
        $periods = $rows->pluck('period')->unique()->values();
        $series = fn ($m) => $periods->map(fn ($p) => round(($rows->first(fn ($r) => $r->metric === $m && $r->period === $p)?->value ?? 0) / 1000, 1))->all();

        return [
            'labels' => $periods->map(fn ($p) => str_replace('2026-', '', $p))->all(),
            'datasets' => [
                ['label' => 'Penjualan', 'data' => $series('penjualan'), 'borderColor' => self::INK, 'backgroundColor' => self::INK, 'yAxisID' => 'y', 'tension' => .3],
                ['label' => 'Laba bruto', 'data' => $series('laba_bruto'), 'borderColor' => self::GOLD, 'backgroundColor' => self::GOLD, 'yAxisID' => 'y', 'tension' => .3],
                ['label' => 'Laba (rugi) bersih', 'data' => $series('laba_bersih'), 'borderColor' => self::BAD, 'backgroundColor' => self::BAD, 'yAxisID' => 'y1', 'tension' => .3],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return $this->legendBottom(['scales' => ['y1' => ['position' => 'right', 'grid' => ['drawOnChartArea' => false]]]]);
    }
}
