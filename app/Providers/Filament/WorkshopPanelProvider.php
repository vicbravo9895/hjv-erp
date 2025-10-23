<?php

namespace App\Providers\Filament;

use App\Filament\Resources\MaintenanceRecordResource;
use App\Filament\Resources\SparePartResource;
use App\Filament\Resources\ProductUsageResource;
use App\Filament\Resources\ProductRequestResource;
use App\Filament\Widgets\WorkshopStatsWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class WorkshopPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('workshop')
            ->path('workshop')
            ->login()
            ->brandName('HJV ERP - Taller')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::Orange,
            ])
            ->resources([
                // Mantenimiento
                MaintenanceRecordResource::class,

                // Inventario
                ProductUsageResource::class,
                SparePartResource::class,
                ProductRequestResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
                \App\Filament\Pages\PanelSwitcher::class,
            ])
            ->widgets([
                Widgets\AccountWidget::class,
                WorkshopStatsWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Mantenimiento')
                    ->icon('heroicon-o-wrench-screwdriver'),
                NavigationGroup::make('Inventario')
                    ->icon('heroicon-o-cube'),
            ])
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
                'workshop.access',
            ])
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full');
    }
}