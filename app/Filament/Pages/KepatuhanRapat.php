<?php

namespace App\Filament\Pages;

use App\Models\MeetingRule;
use App\Services\GovernanceMetrics;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class KepatuhanRapat extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Pengawasan';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Kepatuhan Rapat';

    protected static ?string $title = 'Kepatuhan rapat (POJK 33/2014)';

    protected static ?string $slug = 'kepatuhan-rapat';

    /** Administrator teknis tidak otomatis membaca isi dewan. */
    public static function canAccess(): bool
    {
        return auth()->user()?->role?->value !== 'admin';
    }

    protected string $view = 'filament.pages.kepatuhan-rapat';

    public function getSubheading(): ?string
    {
        return 'Frekuensi rapat, ketepatan bahan, dan kehadiran aktual dibanding aturan yang disimpan sebagai konfigurasi (Tata Kelola → Aturan Rapat).';
    }

    protected function getViewData(): array
    {
        $gov = app(GovernanceMetrics::class);
        $me = auth()->user();

        return [
            'frequency' => $gov->frequency($me),
            'materials' => $gov->materials($me),
            'attendance' => $gov->attendance($me),
            'rules' => MeetingRule::orderBy('code')->get(),
        ];
    }
}
