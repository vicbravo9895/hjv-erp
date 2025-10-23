<?php

namespace App\Providers\Filament;

use App\Filament\Resources\CostCenterResource;
use App\Filament\Resources\ExpenseCategoryResource;
use App\Filament\Resources\ExpenseResource;
use App\Filament\Resources\ProviderResource;
use App\Filament\Resources\WeeklyPayrollResource;
use App\Filament\Resources\PaymentScaleResource;
use App\Filament\Resources\TravelExpenseResource;
use App\Filament\Pages\FinancialReportsPage;
use App\Filament\Widgets\AccountingStatsWidget;
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

class AccountingPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('accounting')
            ->path('accounting')
            ->login()
            ->brandName('HJV ERP - Contabilidad')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => Color::Green,
            ])
            ->resources([
                // Finanzas
                TravelExpenseResource::class,
                ProviderResource::class,
                ExpenseResource::class,
                WeeklyPayrollResource::class,
                CostCenterResource::class,
                ExpenseCategoryResource::class,
                PaymentScaleResource::class,
            ])
            ->pages([
                Pages\Dashboard::class,
                FinancialReportsPage::class,
                \App\Filament\Pages\PanelSwitcher::class,
            ])
            ->widgets([
                Widgets\AccountWidget::class,
                AccountingStatsWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Finanzas')
                    ->icon('heroicon-o-currency-dollar'),
                NavigationGroup::make('Reportes')
                    ->icon('heroicon-o-document-chart-bar'),
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
                'accounting.access',
            ])
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full');
    }
}