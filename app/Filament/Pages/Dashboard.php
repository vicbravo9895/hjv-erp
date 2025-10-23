<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.dashboard';

    protected static ?string $title = 'Panel de Control';

    protected static ?string $navigationLabel = 'Dashboard';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\FleetStatsWidget::class,
            \App\Filament\Widgets\OperationsStatsWidget::class,
            \App\Filament\Widgets\AccountingStatsWidget::class,
            \App\Filament\Widgets\RecentTripsWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return [
            'md' => 2,
            'xl' => 4,
        ];
    }
}