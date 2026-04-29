<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandName('CV QUTBY CREATIVINDO')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->globalSearch(false)
            ->userMenuItems([])
            ->colors([
                'primary' => [
                    50  => '239, 246, 255',
                    100 => '219, 234, 254',
                    200 => '191, 219, 254',
                    300 => '147, 197, 253',
                    400 => '96, 165, 250',
                    500 => '59, 130, 246',
                    600 => '37, 99, 235',
                    700 => '29, 78, 216',
                    800 => '30, 64, 175',
                    900 => '30, 58, 138',
                    950 => '23, 37, 84',
                ],
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
              Dashboard::class,
            ])

            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
            ])
            ->renderHook(
                \Filament\View\PanelsRenderHook::SIMPLE_LAYOUT_START,
                fn () => \Illuminate\Support\Facades\Blade::render('
                    <div class="custom-login-header">
                        <div class="custom-logo-circle">Q</div>
                        <h2 class="company-name">Qutby Collection</h2>
                        <p class="company-tagline">Portal Manajemen Bisnis & Karyawan</p>
                    </div>
                ')
            )
            ->renderHook(
                \Filament\View\PanelsRenderHook::SIDEBAR_FOOTER,
                fn () => view('custom.sidebar-footer')
            )

            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
