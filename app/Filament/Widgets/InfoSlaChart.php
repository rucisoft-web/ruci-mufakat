<?php

namespace App\Filament\Widgets;

class InfoSlaChart extends MufakatChart
{
    protected ?string $heading = 'SLA permintaan informasi';

    protected ?string $description = 'Jawaban manajemen atas pertanyaan Komisaris';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $sla = $this->metrics()->infoSla(auth()->user());

        return [
            'labels' => array_keys($sla),
            'datasets' => [['data' => array_values($sla), 'backgroundColor' => [self::GOOD, self::WARN, self::BAD, self::SKY], 'borderWidth' => 0]],
        ];
    }

    protected function getOptions(): array
    {
        return $this->legendBottom(['scales' => ['x' => ['display' => false], 'y' => ['display' => false]], 'cutout' => '62%']);
    }
}
