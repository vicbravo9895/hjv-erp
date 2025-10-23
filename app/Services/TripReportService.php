<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Trailer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TripReportService
{
    protected ProfitabilityService $profitabilityService;

    public function __construct(ProfitabilityService $profitabilityService)
    {
        $this->profitabilityService = $profitabilityService;
    }

    /**
     * Generate comprehensive trip report by operator.
     */
    public function generateOperatorReport(
        User $operator, 
        \DateTime $startDate, 
        \DateTime $endDate,
        array $revenues = []
    ): array {
        $trips = $operator->trips()
            ->whereBetween('start_date', [$startDate, $endDate])
            ->with(['costs', 'truck', 'trailer'])
            ->orderBy('start_date', 'desc')
            ->get();

        $completedTrips = $trips->where('status', 'completed');
        $activeTrips = $trips->whereIn('status', ['planned', 'in_progress']);
        $cancelledTrips = $trips->where('status', 'cancelled');

        $summary = [
            'total_trips' => $trips->count(),
            'completed_trips' => $completedTrips->count(),
            'active_trips' => $activeTrips->count(),
            'cancelled_trips' => $cancelledTrips->count(),
            'completion_rate' => $trips->count() > 0 ? ($completedTrips->count() / $trips->count()) * 100 : 0,
        ];

        // Add cost and profitability data for completed trips
        if ($completedTrips->count() > 0) {
            $profitabilityData = $this->profitabilityService->calculateOperatorProfitability(
                $operator, 
                $startDate, 
                $endDate, 
                $revenues
            );
            $summary = array_merge($summary, $profitabilityData['summary']);
        }

        return [
            'operator' => [
                'id' => $operator->id,
                'name' => $operator->name,
                'license_number' => $operator->license_number,
            ],
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'summary' => $summary,
            'trips' => $this->formatTripsForReport($trips),
            'cost_breakdown' => $completedTrips->count() > 0 ? 
                $this->profitabilityService->calculateOperatorProfitability($operator, $startDate, $endDate, $revenues)['cost_breakdown'] : 
                ['diesel' => 0, 'tolls' => 0, 'maneuvers' => 0, 'other' => 0],
        ];
    }

    /**
     * Generate comprehensive trip report by vehicle.
     */
    public function generateVehicleReport(
        Vehicle $vehicle, 
        \DateTime $startDate, 
        \DateTime $endDate,
        array $revenues = []
    ): array {
        $trips = $vehicle->trips()
            ->whereBetween('start_date', [$startDate, $endDate])
            ->with(['costs', 'operator', 'trailer'])
            ->orderBy('start_date', 'desc')
            ->get();

        $completedTrips = $trips->where('status', 'completed');
        $activeTrips = $trips->whereIn('status', ['planned', 'in_progress']);
        $cancelledTrips = $trips->where('status', 'cancelled');

        $summary = [
            'total_trips' => $trips->count(),
            'completed_trips' => $completedTrips->count(),
            'active_trips' => $activeTrips->count(),
            'cancelled_trips' => $cancelledTrips->count(),
            'completion_rate' => $trips->count() > 0 ? ($completedTrips->count() / $trips->count()) * 100 : 0,
            'utilization_days' => $this->calculateVehicleUtilizationDays($trips, $startDate, $endDate),
        ];

        // Add cost and profitability data for completed trips
        if ($completedTrips->count() > 0) {
            $profitabilityData = $this->profitabilityService->calculateVehicleProfitability(
                $vehicle, 
                $startDate, 
                $endDate, 
                $revenues
            );
            $summary = array_merge($summary, $profitabilityData['summary']);
        }

        return [
            'vehicle' => [
                'id' => $vehicle->id,
                'name' => $vehicle->display_name,
                'unit_number' => $vehicle->unit_number,
                'plate' => $vehicle->plate,
            ],
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'summary' => $summary,
            'trips' => $this->formatTripsForReport($trips),
            'cost_breakdown' => $completedTrips->count() > 0 ? 
                $this->profitabilityService->calculateVehicleProfitability($vehicle, $startDate, $endDate, $revenues)['cost_breakdown'] : 
                ['diesel' => 0, 'tolls' => 0, 'maneuvers' => 0, 'other' => 0],
        ];
    }

    /**
     * Generate overall fleet report for a period.
     */
    public function generateFleetReport(
        \DateTime $startDate, 
        \DateTime $endDate,
        array $revenues = []
    ): array {
        $trips = Trip::whereBetween('start_date', [$startDate, $endDate])
            ->with(['costs', 'operator', 'truck', 'trailer'])
            ->get();

        $completedTrips = $trips->where('status', 'completed');
        $activeTrips = $trips->whereIn('status', ['planned', 'in_progress']);
        $cancelledTrips = $trips->where('status', 'cancelled');

        // Fleet utilization statistics
        $activeVehicles = $trips->pluck('truck_id')->unique()->count();
        $activeOperators = $trips->pluck('operator_id')->unique()->count();
        $activeTrailers = $trips->whereNotNull('trailer_id')->pluck('trailer_id')->unique()->count();

        $summary = [
            'total_trips' => $trips->count(),
            'completed_trips' => $completedTrips->count(),
            'active_trips' => $activeTrips->count(),
            'cancelled_trips' => $cancelledTrips->count(),
            'completion_rate' => $trips->count() > 0 ? ($completedTrips->count() / $trips->count()) * 100 : 0,
            'active_vehicles' => $activeVehicles,
            'active_operators' => $activeOperators,
            'active_trailers' => $activeTrailers,
        ];

        // Add profitability data
        if ($completedTrips->count() > 0) {
            $profitabilityData = $this->profitabilityService->getOverallProfitabilityStats(
                $startDate, 
                $endDate, 
                $revenues
            );
            $summary = array_merge($summary, $profitabilityData['overall_summary']);
        }

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'summary' => $summary,
            'cost_breakdown' => $completedTrips->count() > 0 ? 
                $profitabilityData['cost_breakdown'] : 
                ['diesel' => 0, 'tolls' => 0, 'maneuvers' => 0, 'other' => 0],
            'operator_performance' => $completedTrips->count() > 0 ? 
                $profitabilityData['operator_stats'] : [],
            'vehicle_performance' => $completedTrips->count() > 0 ? 
                $profitabilityData['vehicle_stats'] : [],
            'recent_trips' => $this->formatTripsForReport($trips->sortByDesc('start_date')->take(20)),
        ];
    }

    /**
     * Generate trip statistics by status for a period.
     */
    public function getTripStatusStatistics(\DateTime $startDate, \DateTime $endDate): array
    {
        $statistics = Trip::whereBetween('start_date', [$startDate, $endDate])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $total = array_sum($statistics);

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'statistics' => [
                'planned' => $statistics['planned'] ?? 0,
                'in_progress' => $statistics['in_progress'] ?? 0,
                'completed' => $statistics['completed'] ?? 0,
                'cancelled' => $statistics['cancelled'] ?? 0,
                'total' => $total,
            ],
            'percentages' => [
                'planned' => $total > 0 ? (($statistics['planned'] ?? 0) / $total) * 100 : 0,
                'in_progress' => $total > 0 ? (($statistics['in_progress'] ?? 0) / $total) * 100 : 0,
                'completed' => $total > 0 ? (($statistics['completed'] ?? 0) / $total) * 100 : 0,
                'cancelled' => $total > 0 ? (($statistics['cancelled'] ?? 0) / $total) * 100 : 0,
            ],
        ];
    }

    /**
     * Get top performing operators by trip count.
     */
    public function getTopOperatorsByTrips(\DateTime $startDate, \DateTime $endDate, int $limit = 10): array
    {
        $operators = DB::table('trips')
            ->join('users', 'trips.operator_id', '=', 'users.id')
            ->where('users.role', 'operador')
            ->whereBetween('trips.start_date', [$startDate, $endDate])
            ->where('trips.status', 'completed')
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(trips.id) as trip_count'),
                DB::raw('AVG(DATEDIFF(trips.end_date, trips.start_date)) as avg_trip_duration')
            )
            ->groupBy('users.id', 'users.name')
            ->orderBy('trip_count', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();

        return $operators;
    }

    /**
     * Get most used routes.
     */
    public function getMostUsedRoutes(\DateTime $startDate, \DateTime $endDate, int $limit = 10): array
    {
        $routes = Trip::whereBetween('start_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->select(
                'origin',
                'destination',
                DB::raw('COUNT(*) as trip_count'),
                DB::raw('AVG(DATEDIFF(end_date, start_date)) as avg_duration')
            )
            ->groupBy('origin', 'destination')
            ->orderBy('trip_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($route) {
                return [
                    'route' => "{$route->origin} → {$route->destination}",
                    'trip_count' => $route->trip_count,
                    'avg_duration' => round($route->avg_duration, 1),
                ];
            })
            ->toArray();

        return $routes;
    }

    /**
     * Calculate vehicle utilization days.
     */
    protected function calculateVehicleUtilizationDays(Collection $trips, \DateTime $startDate, \DateTime $endDate): int
    {
        $utilizationDays = 0;
        $completedTrips = $trips->where('status', 'completed');

        foreach ($completedTrips as $trip) {
            $tripStart = max(new \DateTime($trip->start_date), $startDate);
            $tripEnd = min(new \DateTime($trip->end_date ?? $trip->start_date), $endDate);
            
            if ($tripStart <= $tripEnd) {
                $utilizationDays += $tripStart->diff($tripEnd)->days + 1;
            }
        }

        return $utilizationDays;
    }

    /**
     * Format trips data for reports.
     */
    protected function formatTripsForReport(Collection $trips): array
    {
        return $trips->map(function ($trip) {
            return [
                'id' => $trip->id,
                'origin' => $trip->origin,
                'destination' => $trip->destination,
                'start_date' => $trip->start_date,
                'end_date' => $trip->end_date,
                'status' => $trip->status,
                'operator' => $trip->operator ? $trip->operator->name : null,
                'vehicle' => $trip->truck ? $trip->truck->display_name : null,
                'trailer' => $trip->trailer ? $trip->trailer->display_name : null,
                'total_cost' => $trip->total_cost,
                'completed_at' => $trip->completed_at,
            ];
        })->toArray();
    }
}