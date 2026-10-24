<?php

namespace App\Filament\Widgets;

class MeetingFrequencyChart extends MufakatChart
{
    protected ?string $heading = 'Frekuensi rapat 2026 dan aturan';

    protected ?string $description = 'Terlaksana dan terjadwal dibanding minimum POJK dan kebijakan internal';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $rows = $this->metrics()->frequency(auth()->user());

        return [
            'labels' => $rows->map(fn ($r) => $r['organ']->short_name)->all(),
            'datasets' => [
                ['label' => 'Terlaksana', 'data' => $rows->pluck('held')->all(), 'backgroundColor' => self::INK, 'stack' => 'a', 'borderRadius' => 4],
                ['label' => 'Terjadwal', 'data' => $rows->pluck('planned')->all(), 'backgroundColor' => '#82a3d1', 'stack' => 'a', 'borderRadius' => 4],
                ['label' => 'Gagal kuorum', 'data' => $rows->pluck('failed')->all(), 'backgroundColor' => self::BAD, 'stack' => 'a', 'borderRadius' => 4],
                ['label' => 'Minimum regulasi', 'data' => $rows->pluck('regulatory')->all(), 'type' => 'line', 'borderColor' => self::GOLD, 'backgroundColor' => self::GOLD, 'pointStyle' => 'rectRot', 'pointRadius' => 6, 'showLine' => false],
                ['label' => 'Kebijakan internal', 'data' => $rows->pluck('internal')->all(), 'type' => 'line', 'borderColor' => self::GOOD, 'backgroundColor' => self::GOOD, 'pointStyle' => 'line', 'pointRadius' => 10, 'borderWidth' => 3, 'showLine' => false],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return $this->legendBottom(['scales' => ['x' => ['stacked' => true], 'y' => ['stacked' => true, 'beginAtZero' => true]]]);
    }
}
