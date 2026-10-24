<?php

namespace App\Filament\Pages;

use App\Models\DemoScenario;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class PanduanDemo extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string|UnitEnum|null $navigationGroup = 'Beranda';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Panduan Demo';

    protected static ?string $title = 'Panduan demo 30–40 menit';

    protected static ?string $slug = 'panduan-demo';

    protected string $view = 'filament.pages.panduan-demo';

    public static function shouldRegisterNavigation(): bool
    {
        return DemoScenario::exists();
    }

    public function getSubheading(): ?string
    {
        return 'Urutan skenario untuk memperlihatkan siklus rapat, keputusan, tindak lanjut, AI, dan kasus khusus memakai data demo Kimia Farma.';
    }

    protected function getViewData(): array
    {
        return [
            'stages' => DemoScenario::orderBy('sort')->get()->groupBy('stage'),
            'accounts' => User::where('email', 'like', '%@mufakat.demo')->where('role', '!=', 'tamu_demo')->orderBy('role')->orderBy('name')->get(),
        ];
    }

    public function routeFor(?string $hint): ?string
    {
        if (! $hint) {
            return null;
        }

        try {
            return url('/app/'.$hint);
        } catch (\Throwable) {
            return null;
        }
    }
}
