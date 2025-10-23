<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
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

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('HJV ERP')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::Blue,
            ])
            ->resources([
                // Gestión de Flota
                \App\Filament\Resources\VehicleResource::class,
                \App\Filament\Resources\TrailerResource::class,

                // Operaciones
                \App\Filament\Resources\TripResource::class,
                \App\Filament\Resources\TripCostResource::class,

                // Mantenimiento
                \App\Filament\Resources\MaintenanceRecordResource::class,

                // Inventario
                \App\Filament\Resources\ProductUsageResource::class,
                \App\Filament\Resources\SparePartResource::class,
                \App\Filament\Resources\ProductRequestResource::class,

                // Finanzas
                \App\Filament\Resources\TravelExpenseResource::class,
                \App\Filament\Resources\ProviderResource::class,
                \App\Filament\Resources\ExpenseResource::class,
                \App\Filament\Resources\WeeklyPayrollResource::class,
                \App\Filament\Resources\CostCenterResource::class,
                \App\Filament\Resources\ExpenseCategoryResource::class,
                \App\Filament\Resources\PaymentScaleResource::class,

                // Sistema
                \App\Filament\Resources\UserResource::class,
                \App\Filament\Resources\SamsaraSyncLogResource::class,
            ])
            ->pages([
                \App\Filament\Pages\Dashboard::class,
                \App\Filament\Pages\SamsaraIntegrationPage::class,
            ])
            ->widgets([
                \App\Filament\Widgets\FleetStatsWidget::class,
                \App\Filament\Widgets\OperationsStatsWidget::class,
                \App\Filament\Widgets\AccountingStatsWidget::class,
                \App\Filament\Widgets\RecentTripsWidget::class,
                Widgets\AccountWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Gestión de Flota')
                    ->icon('heroicon-o-truck')
                    ->collapsed(),
                NavigationGroup::make('Operaciones')
                    ->icon('heroicon-o-map')
                    ->collapsed(),
                NavigationGroup::make('Mantenimiento')
                    ->icon('heroicon-o-wrench-screwdriver'),
                NavigationGroup::make('Inventario')
                    ->icon('heroicon-o-cube')
                    ->collapsed(),
                NavigationGroup::make('Finanzas')
                    ->icon('heroicon-o-currency-dollar')
                    ->collapsed(),
                NavigationGroup::make('Sistema')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(),
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
                'admin.access',
            ])
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full');
    }
}
