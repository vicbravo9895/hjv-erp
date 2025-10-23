<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use App\Models\TripCost;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OperationsStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $currentMonth = now()->startOfMonth();
        $previousMonth = now()->subMonth()->startOfMonth();

        // Current month trips
        $currentMonthTrips = Trip::whereBetween('start_date', [
            $currentMonth,
            now()->endOfMonth()
        ])->count();

        // Previous month trips for comparison
        $previousMonthTrips = Trip::whereBetween('start_date', [
            $previousMonth,
            $previousMonth->copy()->endOfMonth()
        ])->count();

        // Active trips (in progress)
        $activeTrips = Trip::where('status', 'in_progress')->count();

        // Completed trips this month
        $completedTrips = Trip::where('status', 'completed')
            ->whereBetween('start_date', [$currentMonth, now()->endOfMonth()])
            ->count();

        // Average trip cost this month
        $avgTripCost = Trip::whereBetween('start_date', [$currentMonth, now()->endOfMonth()])
            ->whereHas('costs')
            ->withSum('costs', 'amount')
            ->get()
            ->avg('costs_sum_amount') ?? 0;

        // Total trip costs this month
        $totalTripCosts = TripCost::whereHas('trip', function ($query) use ($currentMonth) {
            $query->whereBetween('start_date', [$currentMonth, now()->endOfMonth()]);
        })->sum('amount');

        return [
            Stat::make('Viajes del Mes', $currentMonthTrips)
                ->description($this->getChangeDescription($currentMonthTrips, $previousMonthTrips))
                ->descriptionIcon($currentMonthTrips >= $previousMonthTrips ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($currentMonthTrips >= $previousMonthTrips ? 'success' : 'warning')
                ->chart($this->getTripsChart()),

            Stat::make('Viajes Activos', $activeTrips)
                ->description('En progreso actualmente')
                ->descriptionIcon('heroicon-m-clock')
                ->color($activeTrips > 0 ? 'warning' : 'success'),

            Stat::make('Viajes Completados', $completedTrips)
                ->description('Finalizados este mes')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Costo Promedio', '$' . number_format($avgTripCost, 2))
                ->description('Por viaje este mes')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info'),
        ];
    }

    private function getChangeDescription(int $current, int $previous): string
    {
        if ($previous == 0) {
            return $current > 0 ? 'Nuevos viajes este mes' : 'Sin viajes';
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

    private function getTripsChart(): array
    {
        // Chart data for trips over last 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $data[] = Trip::whereDate('start_date', $date)->count();
        }
        return $data;
    }
}