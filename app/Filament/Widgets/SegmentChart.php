<?php

namespace App\Filament\Widgets;

use App\Models\FinancialFact;

class SegmentChart extends MufakatChart
{
    protected ?string $heading = 'Penjualan per segmen 2025';

    protected ?string $description = 'Rp miliar · Diturunkan dari laporan hlm. 21';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $rows = FinancialFact::where('company_id', $this->company()?->id)->where('metric', 'like', 'segmen_%')->get();

        return [
            'labels' => $rows->map(fn ($r) => ucfirst(str_replace('Penjualan segmen ', '', $r->label)))->all(),
            'datasets' => [['data' => $rows->pluck('value')->map(fn ($v) => (float) $v)->all(), 'backgroundColor' => [self::INK, self::GOLD, self::SKY, self::SLATE], 'borderWidth' => 0]],
        ];
    }

    protected function getOptions(): array
    {
        return $this->legendBottom(['scales' => ['x' => ['display' => false], 'y' => ['display' => false]], 'cutout' => '62%']);
    }
}
