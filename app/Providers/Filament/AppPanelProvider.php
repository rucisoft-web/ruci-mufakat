<?php

namespace App\Providers\Filament;

use App\Filament\Pages\BerandaSaya;
use App\Http\Middleware\RestrictDemoGuest;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('app')
            ->path('app')
            ->login()
            ->passwordReset()
            ->profile()
            ->brandName('Ruci MUFAKAT')
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('2.1rem')
            ->favicon(asset('favicon.svg'))
            ->colors([
                // Biru tinta ruang dewan; emas dipakai sebagai aksen di CSS.
                'primary' => [
                    50 => '#eef3fa', 100 => '#d8e3f2', 200 => '#b1c6e4', 300 => '#82a3d1', 400 => '#5580bb',
                    500 => '#3563a3', 600 => '#244d89', 700 => '#1c3d6e', 800 => '#16305a', 900 => '#112647', 950 => '#0a1830',
                ],
                'warning' => Color::Amber,
                'gray' => Color::Slate,
            ])
            ->font('Inter')
            ->multiFactorAuthentication(
                [AppAuthentication::make()->recoverable()],
                isRequired: fn () => config('mufakat.mfa_required') && ! auth()->user()?->isDemoGuest(),
            )
            ->navigationGroups([
                NavigationGroup::make('Beranda'),
                NavigationGroup::make('Rapat'),
                NavigationGroup::make('Keputusan & Tindak Lanjut'),
                NavigationGroup::make('Pengawasan'),
                NavigationGroup::make('Asisten AI'),
                NavigationGroup::make('Tata Kelola'),
                NavigationGroup::make('Administrasi')->collapsed(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->homeUrl(fn () => BerandaSaya::getUrl())
            ->widgets([])
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s')
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->globalSearch(false)
            ->renderHook(PanelsRenderHook::SIMPLE_LAYOUT_START, fn () => auth()->guest() ? view('filament.auth.hero') : '')
            ->renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, fn () => view('filament.auth.login-links'))
            ->renderHook(PanelsRenderHook::CONTENT_START, fn () => view('filament.demo-banner'))
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => new HtmlString('<link rel="stylesheet" href="'.asset('css/mufakat.css').'?v='.@filemtime(public_path('css/mufakat.css')).'">'))
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class, RestrictDemoGuest::class]);
    }
}
