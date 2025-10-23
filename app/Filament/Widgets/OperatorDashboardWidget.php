<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use App\Models\TravelExpense;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class OperatorDashboardWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        
        if (!$user || !$user->isOperator()) {
            return [];
        }

        // Get operator's statistics
        $activeTrips = Trip::where('operator_id', $user->id)->active()->count();
        $completedTrips = Trip::where('operator_id', $user->id)->completed()->count();
        
        $pendingExpenses = TravelExpense::where('operator_id', $user->id)
            ->where('status', 'pending')
            ->count();
        
        $pendingAmount = TravelExpense::where('operator_id', $user->id)
            ->where('status', 'pending')
            ->sum('amount');
        
        $approvedAmount = TravelExpense::where('operator_id', $user->id)
            ->where('status', 'approved')
            ->sum('amount');
        
        $thisMonthExpenses = TravelExpense::where('operator_id', $user->id)
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');

        return [
            Stat::make('Viajes Activos', $activeTrips)
                ->description('Viajes en progreso o planeados')
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning'),
            
            Stat::make('Viajes Completados', $completedTrips)
                ->description('Total de viajes finalizados')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            
            Stat::make('Gastos Pendientes', $pendingExpenses)
                ->description('Gastos por aprobar')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            
            Stat::make('Monto Pendiente', '$' . number_format($pendingAmount, 2))
                ->description('Total pendiente de aprobación')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('danger'),
            
            Stat::make('Monto Aprobado', '$' . number_format($approvedAmount, 2))
                ->description('Total aprobado para reembolso')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
            
            Stat::make('Gastos del Mes', '$' . number_format($thisMonthExpenses, 2))
                ->description('Total gastado este mes')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }
}