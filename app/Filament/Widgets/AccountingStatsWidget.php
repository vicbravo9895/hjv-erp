<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\WeeklyPayroll;
use App\Models\Provider;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AccountingStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $currentMonth = now()->startOfMonth();
        $previousMonth = now()->subMonth()->startOfMonth();
        
        // Current month expenses
        $currentMonthExpenses = Expense::whereBetween('date', [
            $currentMonth,
            now()->endOfMonth()
        ])->sum('amount');
        
        // Previous month expenses for comparison
        $previousMonthExpenses = Expense::whereBetween('date', [
            $previousMonth,
            $previousMonth->copy()->endOfMonth()
        ])->sum('amount');
        
        // Current month payroll
        $currentMonthPayroll = WeeklyPayroll::whereBetween('week_start', [
            $currentMonth,
            now()->endOfMonth()
        ])->sum('total_payment');
        
        // Previous month payroll for comparison
        $previousMonthPayroll = WeeklyPayroll::whereBetween('week_start', [
            $previousMonth,
            $previousMonth->copy()->endOfMonth()
        ])->sum('total_payment');
        
        // Total providers
        $totalProviders = Provider::count();
        
        // Active providers (with expenses this month)
        $activeProviders = Provider::whereHas('expenses', function ($query) use ($currentMonth) {
            $query->whereBetween('date', [$currentMonth, now()->endOfMonth()]);
        })->count();

        return [
            Stat::make('Gastos del Mes', '$' . number_format($currentMonthExpenses, 2))
                ->description($this->getChangeDescription($currentMonthExpenses, $previousMonthExpenses))
                ->descriptionIcon($currentMonthExpenses >= $previousMonthExpenses ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($currentMonthExpenses >= $previousMonthExpenses ? 'danger' : 'success'),
                
            Stat::make('Nómina del Mes', '$' . number_format($currentMonthPayroll, 2))
                ->description($this->getChangeDescription($currentMonthPayroll, $previousMonthPayroll))
                ->descriptionIcon($currentMonthPayroll >= $previousMonthPayroll ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($currentMonthPayroll >= $previousMonthPayroll ? 'warning' : 'success'),
                
            Stat::make('Total Mensual', '$' . number_format($currentMonthExpenses + $currentMonthPayroll, 2))
                ->description('Gastos + Nómina')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('primary'),
                
            Stat::make('Proveedores Activos', $activeProviders . ' / ' . $totalProviders)
                ->description('Proveedores con gastos este mes')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('info'),
        ];
    }
    
    private function getChangeDescription(float $current, float $previous): string
    {
        if ($previous == 0) {
            return $current > 0 ? 'Nuevo gasto este mes' : 'Sin gastos';
        }
        
        $change = (($current - $previous) / $previous) * 100;
        $changeFormatted = number_format(abs($change), 1);
        
        if ($change > 0) {
            return "+{$changeFormatted}% vs mes anterior";
        } elseif ($change < 0) {
            return "-{$changeFormatted}% vs mes anterior";
        } else {
            return 'Sin cambio vs mes anterior';
        }
    }
}
