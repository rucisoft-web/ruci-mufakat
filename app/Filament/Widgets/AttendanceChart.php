<?php

namespace App\Filament\Widgets;

class AttendanceChart extends MufakatChart
{
    protected ?string $heading = 'Kehadiran aktual anggota 2026';

    protected ?string $description = 'Persen rapat yang dihadiri (bukan RSVP); garis kebijakan 85%';

    protected ?string $maxHeight = '320px';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $rows = $this->metrics()->attendance(auth()->user())->take(14);

        return [
            'labels' => $rows->map(fn ($r) => str_replace('Demo ', '', $r['user']->name))->all(),
            'datasets' => [[
                'label' => 'Kehadiran (%)',
                'data' => $rows->map(fn ($r) => round($r['pct'], 1))->all(),
                'backgroundColor' => $rows->map(fn ($r) => $r['pct'] < 85 ? self::WARN : self::INK)->all(),
                'borderRadius' => 4,
            ]],
        ];
    }

    protected function getOptions(): array
    {
        return ['indexAxis' => 'y', 'plugins' => ['legend' => ['display' => false]], 'scales' => ['x' => ['min' => 0, 'max' => 100]]];
    }
}
