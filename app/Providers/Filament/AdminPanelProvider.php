<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use BezhanSalleh\GoogleAnalytics\GoogleAnalyticsPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Skular Admin')
            ->font('Rubik')
            ->colors([
                // Skular amber-to-orange primary palette.
                'primary' => [
                    50 => '#fdfaef',
                    100 => '#fcf3cf',
                    200 => '#fae79b',
                    300 => '#f6d969',
                    400 => '#f2c94c',
                    500 => '#f0b34b',
                    600 => '#f2994a',
                    700 => '#cf7a34',
                    800 => '#a85c2b',
                    900 => '#864826',
                    950 => '#4a2411',
                ],
                // Skular teal for info accents.
                'info' => [
                    50 => '#eef7f9',
                    100 => '#d4ebef',
                    200 => '#a9d7df',
                    300 => '#7cc0cd',
                    400 => '#56a5b2',
                    500 => '#3d8b9a',
                    600 => '#277c93',
                    700 => '#206476',
                    800 => '#1d5361',
                    900 => '#1c4652',
                    950 => '#0c2c35',
                ],
                'gray' => Color::Zinc,
            ])
            ->sidebarCollapsibleOnDesktop()
            // Groups follow the work of the curriculum team: build the
            // curriculum, write the material and exercises, connect it to
            // scholarships, then watch how mentees progress.
            ->navigationGroups([
                NavigationGroup::make('Kurikulum'),
                NavigationGroup::make('Materi & Latihan'),
                NavigationGroup::make('Beasiswa'),
                NavigationGroup::make('Konten Publik'),
                NavigationGroup::make('Pemantauan Mentee'),
                NavigationGroup::make('Pendaftaran Beasiswa'),
                NavigationGroup::make('Administrasi Sistem'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
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
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
                BreezyCore::make(),
                GoogleAnalyticsPlugin::make(),
            ]);
    }
}
