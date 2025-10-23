<?php

namespace App\Providers\Filament;

use App\Filament\Resources\TravelExpenseResource;
use App\Filament\Resources\OperatorTripResource;
use App\Filament\Resources\OperatorVehicleResource;
use App\Filament\Resources\TripResource;
use App\Filament\Resources\TripCostResource;
use App\Filament\Widgets\OperatorDashboardWidget;
use App\Filament\Widgets\OperatorExpenseStatsWidget;
use App\Filament\Widgets\OperatorRecentExpensesWidget;
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

class OperatorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('operator')
            ->path('operator')
            ->login()
            ->brandName('HJV ERP - Operador')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::Green,
            ])
            ->resources([
                // Mi Vehículo
                OperatorVehicleResource::class,

                // Operaciones
                OperatorTripResource::class,
                TripResource::class,
                TripCostResource::class,

                // Finanzas
                TravelExpenseResource::class,
            ])
            ->discoverResources(in: app_path('Filament/Operator/Resources'), for: 'App\\Filament\\Operator\\Resources')
            ->pages([
                Pages\Dashboard::class,
                \App\Filament\Pages\PanelSwitcher::class,
            ])
            ->widgets([
                Widgets\AccountWidget::class,
                OperatorDashboardWidget::class,
                OperatorExpenseStatsWidget::class,
                OperatorRecentExpensesWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Mi Vehículo')
                    ->icon('heroicon-o-truck'),
                NavigationGroup::make('Mis Viajes')
                    ->icon('heroicon-o-map-pin'),
                NavigationGroup::make('Operaciones')
                    ->icon('heroicon-o-map')
                    ->collapsed(),
                NavigationGroup::make('Finanzas')
                    ->icon('heroicon-o-currency-dollar'),
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
                'operator.access',
            ])
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full');
    }
}