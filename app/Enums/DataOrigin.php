<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DataOrigin: string implements HasColor, HasLabel
{
    case ReportFact = 'report_fact';
    case DerivedFromReport = 'derived_from_report';
    case PublicFact = 'public_fact';
    case Simulated = 'simulated';

    public function getLabel(): string
    {
        return match ($this) {
            self::ReportFact => 'Fakta laporan',
            self::DerivedFromReport => 'Turunan dari laporan',
            self::PublicFact => 'Fakta publik',
            self::Simulated => 'Simulasi',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ReportFact => 'success',
            self::DerivedFromReport => 'info',
            self::PublicFact => 'primary',
            self::Simulated => 'warning',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->getLabel()])->all();
    }
}
