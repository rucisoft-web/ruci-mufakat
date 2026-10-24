<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Services\FinancialAnalysis;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class KinerjaKeuangan extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Pengawasan';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Kinerja Keuangan';

    protected static ?string $title = 'Kinerja keuangan';

    protected static ?string $slug = 'kinerja-keuangan';

    /** Administrator teknis tidak otomatis membaca isi dewan. */
    public static function canAccess(): bool
    {
        return auth()->user()?->role?->value !== 'admin';
    }

    protected string $view = 'filament.pages.kinerja-keuangan';

    public function getSubheading(): ?string
    {
        return 'Setiap angka menyebut asal data dan halaman sumber. Rasio dan pertumbuhan dihitung aplikasi secara deterministik; AI hanya menjelaskan.';
    }

    protected function getViewData(): array
    {
        $company = Company::whereNull('parent_id')->first();
        $fin = app(FinancialAnalysis::class);

        return [
            'company' => $company,
            'growth' => $company ? $fin->growth($company) : [],
            'ratios' => $company ? $fin->ratios($company) : [],
            'flags' => $company ? $fin->qualityFlags($company) : [],
            'facts' => $company ? $company->financialFacts()->with('document')->orderBy('period_type')->orderBy('metric')->orderBy('period')->get() : collect(),
        ];
    }
}
