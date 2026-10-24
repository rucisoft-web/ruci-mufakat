<?php

namespace App\Filament\Widgets;

class TaskStatusChart extends MufakatChart
{
    protected ?string $heading = 'Tindak lanjut per status';

    protected ?string $description = 'Selesai hanya setelah bukti diverifikasi';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $t = $this->metrics()->tasks(auth()->user());

        return [
            'labels' => array_keys($t['by_status']),
            'datasets' => [['data' => array_values($t['by_status']), 'backgroundColor' => [self::SLATE, self::SKY, self::WARN, self::GOOD, self::BAD], 'borderWidth' => 0]],
        ];
    }

    protected function getOptions(): array
    {
        return $this->legendBottom(['scales' => ['x' => ['display' => false], 'y' => ['display' => false]], 'cutout' => '62%']);
    }
}
