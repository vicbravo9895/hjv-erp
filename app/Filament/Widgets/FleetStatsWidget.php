<?php

namespace App\Filament\Widgets;

use App\Models\Vehicle;
use App\Models\Trailer;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FleetStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Vehicle statistics
        $totalVehicles = Vehicle::count();
        $availableVehicles = Vehicle::where('status', 'available')->count();
        $vehiclesInTrip = Vehicle::where('status', 'in_trip')->count();
        $vehiclesInMaintenance = Vehicle::where('status', 'maintenance')->count();

        // Trailer statistics
        $totalTrailers = Trailer::count();
        $availableTrailers = Trailer::where('status', 'available')->count();

        // Operator statistics
        $totalOperators = User::operators()->count();
        $activeOperators = User::activeOperators()->count();

        // Utilization rates
        $vehicleUtilization = $totalVehicles > 0 ? round(($vehiclesInTrip / $totalVehicles) * 100, 1) : 0;
        $trailerUtilization = $totalTrailers > 0 ? round((Trailer::where('status', 'in_trip')->count() / $totalTrailers) * 100, 1) : 0;

        return [
            Stat::make('Tractocamiones', $totalVehicles)
                ->description("{$availableVehicles} disponibles, {$vehiclesInTrip} en viaje")
                ->descriptionIcon('heroicon-m-truck')
                ->color('primary')
                ->chart($this->getVehicleStatusChart()),

            Stat::make('Trailers', $totalTrailers)
                ->description("{$availableTrailers} disponibles")
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('success'),

            Stat::make('Operadores', $totalOperators)
                ->description("{$activeOperators} activos")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('Utilización de Flota', "{$vehicleUtilization}%")
                ->description("Tractocamiones en operación")
                ->descriptionIcon($vehicleUtilization > 70 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($vehicleUtilization > 70 ? 'success' : ($vehicleUtilization > 50 ? 'warning' : 'danger')),
        ];
    }

    private function getVehicleStatusChart(): array
    {
        // Simple chart data for vehicle status over last 7 days
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            // For now, we'll use current data as historical data isn't tracked
            // In a real implementation, you'd query historical status data
            $data[] = Vehicle::where('status', 'in_trip')->count();
        }
        return $data;
    }
}