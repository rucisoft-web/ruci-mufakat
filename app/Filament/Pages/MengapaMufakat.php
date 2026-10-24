<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class MengapaMufakat extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|UnitEnum|null $navigationGroup = 'Beranda';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Manfaat bagi Pimpinan';

    protected static ?string $title = 'Manfaat bagi Komisaris dan Direksi';

    protected static ?string $slug = 'manfaat';

    protected string $view = 'filament.pages.mengapa-mufakat';

    public function getHeading(): string
    {
        return '';
    }
}
