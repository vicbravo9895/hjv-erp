<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\TripCost;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class ProfitabilityService
{
    /**
     * Calculate profitability for a single trip.
     */
    public function calculateTripProfitability(Trip $trip, ?float $revenue = null): array
    {
        $totalCosts = $this->getTripTotalCosts($trip);
        $costBreakdown = $this->getTripCostBreakdown($trip);
        
        $profitability = [
            'trip_id' => $trip->id,
            'trip_name' => $trip->display_name,
            'total_costs' => $totalCosts,
            'cost_breakdown' => $costBreakdown,
            'revenue' => $revenue,
            'profit' => $revenue !== null ? ($revenue - $totalCosts) : null,
            'profit_margin' => $revenue && $revenue > 0 ? (($revenue - $totalCosts) / $revenue) * 100 : null,
        ];

        return $profitability;
    }

    /**
     * Calculate profitability for multiple trips.
     */
    public function calculateMultipleTripsProfitability($trips, array $revenues = []): array
    {
        $results = [];
        
        foreach ($trips as $trip) {
            $revenue = $revenues[$trip->id] ?? null;
            $results[] = $this->calculateTripProfitability($trip, $revenue);
        }

        return $results;
    }

    /**
     * Calculate profitability by operator for a given period.
     */
    public function calculateOperatorProfitability(
        User $operator, 
        \DateTime $startDate, 
        \DateTime $endDate,
        array $revenues = []
    ): array {
        $trips = $operator->trips()
            ->whereBetween('start_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->with('costs')
            ->get();

        $totalCosts = 0;
        $totalRevenue = 0;
        $tripCount = $trips->count();
        $costBreakdown = [
            'diesel' => 0,
            'tolls' => 0,
            'maneuvers' => 0,
            'other' => 0,
        ];

        $tripDetails = [];

        foreach ($trips as $trip) {
            $tripCosts = $this->getTripTotalCosts($trip);
            $tripBreakdown = $this->getTripCostBreakdown($trip);
            $tripRevenue = $revenues[$trip->id] ?? 0;

            $totalCosts += $tripCosts;
            $totalRevenue += $tripRevenue;

            foreach ($costBreakdown as $type => $amount) {
                $costBreakdown[$type] += $tripBreakdown[$type] ?? 0;
            }

            $tripDetails[] = $this->calculateTripProfitability($trip, $tripRevenue);
        }

        return [
            'operator_id' => $operator->id,
            'operator_name' => $operator->name,
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'summary' => [
                'trip_count' => $tripCount,
                'total_costs' => $totalCosts,
                'total_revenue' => $totalRevenue,
                'total_profit' => $totalRevenue - $totalCosts,
                'profit_margin' => $totalRevenue > 0 ? (($totalRevenue - $totalCosts) / $totalRevenue) * 100 : 0,
                'average_cost_per_trip' => $tripCount > 0 ? $totalCosts / $tripCount : 0,
                'average_revenue_per_trip' => $tripCount > 0 ? $totalRevenue / $tripCount : 0,
            ],
            'cost_breakdown' => $costBreakdown,
            'trips' => $tripDetails,
        ];
    }

    /**
     * Calculate profitability by vehicle for a given period.
     */
    public function calculateVehicleProfitability(
        Vehicle $vehicle, 
        \DateTime $startDate, 
        \DateTime $endDate,
        array $revenues = []
    ): array {
        $trips = $vehicle->trips()
            ->whereBetween('start_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->with('costs')
            ->get();

        $totalCosts = 0;
        $totalRevenue = 0;
        $tripCount = $trips->count();
        $costBreakdown = [
            'diesel' => 0,
            'tolls' => 0,
            'maneuvers' => 0,
            'other' => 0,
        ];

        $tripDetails = [];

        foreach ($trips as $trip) {
            $tripCosts = $this->getTripTotalCosts($trip);
            $tripBreakdown = $this->getTripCostBreakdown($trip);
            $tripRevenue = $revenues[$trip->id] ?? 0;

            $totalCosts += $tripCosts;
            $totalRevenue += $tripRevenue;

            foreach ($costBreakdown as $type => $amount) {
                $costBreakdown[$type] += $tripBreakdown[$type] ?? 0;
            }

            $tripDetails[] = $this->calculateTripProfitability($trip, $tripRevenue);
        }

        return [
            'vehicle_id' => $vehicle->id,
            'vehicle_name' => $vehicle->display_name,
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'summary' => [
                'trip_count' => $tripCount,
                'total_costs' => $totalCosts,
                'total_revenue' => $totalRevenue,
                'total_profit' => $totalRevenue - $totalCosts,
                'profit_margin' => $totalRevenue > 0 ? (($totalRevenue - $totalCosts) / $totalRevenue) * 100 : 0,
                'average_cost_per_trip' => $tripCount > 0 ? $totalCosts / $tripCount : 0,
                'average_revenue_per_trip' => $tripCount > 0 ? $totalRevenue / $tripCount : 0,
            ],
            'cost_breakdown' => $costBreakdown,
            'trips' => $tripDetails,
        ];
    }

    /**
     * Get overall profitability statistics for a period.
     */
    public function getOverallProfitabilityStats(
        \DateTime $startDate, 
        \DateTime $endDate,
        array $revenues = []
    ): array {
        $trips = Trip::whereBetween('start_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->with(['costs', 'operator', 'truck'])
            ->get();

        $totalCosts = 0;
        $totalRevenue = 0;
        $tripCount = $trips->count();
        
        $costBreakdown = [
            'diesel' => 0,
            'tolls' => 0,
            'maneuvers' => 0,
            'other' => 0,
        ];

        $operatorStats = [];
        $vehicleStats = [];

        foreach ($trips as $trip) {
            $tripCosts = $this->getTripTotalCosts($trip);
            $tripBreakdown = $this->getTripCostBreakdown($trip);
            $tripRevenue = $revenues[$trip->id] ?? 0;

            $totalCosts += $tripCosts;
            $totalRevenue += $tripRevenue;

            foreach ($costBreakdown as $type => $amount) {
                $costBreakdown[$type] += $tripBreakdown[$type] ?? 0;
            }

            // Operator statistics
            $operatorId = $trip->operator_id;
            if (!isset($operatorStats[$operatorId])) {
                $operatorStats[$operatorId] = [
                    'name' => $trip->operator->name,
                    'trip_count' => 0,
                    'total_costs' => 0,
                    'total_revenue' => 0,
                ];
            }
            $operatorStats[$operatorId]['trip_count']++;
            $operatorStats[$operatorId]['total_costs'] += $tripCosts;
            $operatorStats[$operatorId]['total_revenue'] += $tripRevenue;

            // Vehicle statistics
            $vehicleId = $trip->truck_id;
            if (!isset($vehicleStats[$vehicleId])) {
                $vehicleStats[$vehicleId] = [
                    'name' => $trip->truck->display_name,
                    'trip_count' => 0,
                    'total_costs' => 0,
                    'total_revenue' => 0,
                ];
            }
            $vehicleStats[$vehicleId]['trip_count']++;
            $vehicleStats[$vehicleId]['total_costs'] += $tripCosts;
            $vehicleStats[$vehicleId]['total_revenue'] += $tripRevenue;
        }

        // Calculate profit margins for operators and vehicles
        foreach ($operatorStats as &$stats) {
            $stats['profit'] = $stats['total_revenue'] - $stats['total_costs'];
            $stats['profit_margin'] = $stats['total_revenue'] > 0 ? 
                (($stats['total_revenue'] - $stats['total_costs']) / $stats['total_revenue']) * 100 : 0;
        }

        foreach ($vehicleStats as &$stats) {
            $stats['profit'] = $stats['total_revenue'] - $stats['total_costs'];
            $stats['profit_margin'] = $stats['total_revenue'] > 0 ? 
                (($stats['total_revenue'] - $stats['total_costs']) / $stats['total_revenue']) * 100 : 0;
        }

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'overall_summary' => [
                'trip_count' => $tripCount,
                'total_costs' => $totalCosts,
                'total_revenue' => $totalRevenue,
                'total_profit' => $totalRevenue - $totalCosts,
                'profit_margin' => $totalRevenue > 0 ? (($totalRevenue - $totalCosts) / $totalRevenue) * 100 : 0,
                'average_cost_per_trip' => $tripCount > 0 ? $totalCosts / $tripCount : 0,
                'average_revenue_per_trip' => $tripCount > 0 ? $totalRevenue / $tripCount : 0,
            ],
            'cost_breakdown' => $costBreakdown,
            'operator_stats' => array_values($operatorStats),
            'vehicle_stats' => array_values($vehicleStats),
        ];
    }

    /**
     * Get the most and least profitable trips for a period.
     */
    public function getProfitabilityRanking(
        \DateTime $startDate, 
        \DateTime $endDate,
        array $revenues = [],
        int $limit = 10
    ): array {
        $trips = Trip::whereBetween('start_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->with(['costs', 'operator', 'truck'])
            ->get();

        $profitabilityData = [];

        foreach ($trips as $trip) {
            $tripRevenue = $revenues[$trip->id] ?? 0;
            if ($tripRevenue > 0) { // Only include trips with revenue data
                $profitabilityData[] = $this->calculateTripProfitability($trip, $tripRevenue);
            }
        }

        // Sort by profit margin
        usort($profitabilityData, function ($a, $b) {
            return ($b['profit_margin'] ?? 0) <=> ($a['profit_margin'] ?? 0);
        });

        return [
            'most_profitable' => array_slice($profitabilityData, 0, $limit),
            'least_profitable' => array_slice(array_reverse($profitabilityData), 0, $limit),
        ];
    }

    /**
     * Get total costs for a trip.
     */
    protected function getTripTotalCosts(Trip $trip): float
    {
        return (float) $trip->costs()->sum('amount');
    }

    /**
     * Get cost breakdown by type for a trip.
     */
    protected function getTripCostBreakdown(Trip $trip): array
    {
        $costs = $trip->costs()
            ->select('cost_type', DB::raw('SUM(amount) as total'))
            ->groupBy('cost_type')
            ->pluck('total', 'cost_type')
            ->toArray();

        return [
            'diesel' => (float) ($costs[TripCost::TYPE_DIESEL] ?? 0),
            'tolls' => (float) ($costs[TripCost::TYPE_TOLLS] ?? 0),
            'maneuvers' => (float) ($costs[TripCost::TYPE_MANEUVERS] ?? 0),
            'other' => (float) ($costs[TripCost::TYPE_OTHER] ?? 0),
        ];
    }
}