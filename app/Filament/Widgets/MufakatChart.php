<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Services\GovernanceMetrics;
use Filament\Widgets\ChartWidget;

/** Dasar grafik Ruci MUFAKAT: palet tinta–emas dan satu cara mengambil data. */
abstract class MufakatChart extends ChartWidget
{
    public const INK = '#1c3d6e';

    public const GOLD = '#b8892f';

    public const SLATE = '#94a3b8';

    public const GOOD = '#15803d';

    public const BAD = '#b91c1c';

    public const WARN = '#d97706';

    public const SKY = '#0369a1';

    protected ?string $maxHeight = '260px';

    protected function metrics(): GovernanceMetrics
    {
        return app(GovernanceMetrics::class);
    }

    protected function company(): ?Company
    {
        return Company::whereNull('parent_id')->first();
    }

    protected function legendBottom(array $extra = []): array
    {
        return array_replace_recursive(['plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['boxWidth' => 12]]]], $extra);
    }
}
