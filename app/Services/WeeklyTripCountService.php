<?php

namespace App\Services;

use App\Models\User;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WeeklyTripCountService
{
    /**
     * Count completed trips for an operator in a specific week
     */
    public function countTripsForWeek(int $operatorId, Carbon $weekStart): int
    {
        $weekEnd = $weekStart->copy()->endOfWeek();

        return Trip::where('operator_id', $operatorId)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$weekStart, $weekEnd])
            ->count();
    }

    /**
     * Count completed trips for an operator between two dates
     */
    public function countTripsForPeriod(int $operatorId, Carbon $startDate, Carbon $endDate): int
    {
        return Trip::where('operator_id', $operatorId)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * Get trip counts for all operators for a specific week
     */
    public function getWeeklyTripCountsForAllOperators(Carbon $weekStart): Collection
    {
        $weekEnd = $weekStart->copy()->endOfWeek();

        return Trip::where('status', 'completed')
            ->whereBetween('completed_at', [$weekStart, $weekEnd])
            ->selectRaw('operator_id, COUNT(*) as trips_count')
            ->groupBy('operator_id')
            ->with('operator:id,name')
            ->get()
            ->keyBy('operator_id');
    }

    /**
     * Get detailed trip information for an operator in a specific week
     */
    public function getTripsForWeek(int $operatorId, Carbon $weekStart): Collection
    {
        $weekEnd = $weekStart->copy()->endOfWeek();

        return Trip::where('operator_id', $operatorId)
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$weekStart, $weekEnd])
            ->with(['truck:id,unit_number', 'trailer:id,asset_number'])
            ->orderBy('completed_at')
            ->get();
    }

    /**
     * Get the week start date for a given date (Monday)
     */
    public function getWeekStart(Carbon $date): Carbon
    {
        return $date->copy()->startOfWeek();
    }

    /**
     * Get the week end date for a given date (Sunday)
     */
    public function getWeekEnd(Carbon $date): Carbon
    {
        return $date->copy()->endOfWeek();
    }

    /**
     * Generate week ranges for a given period
     */
    public function generateWeekRanges(Carbon $startDate, Carbon $endDate): Collection
    {
        $weeks = collect();
        $current = $this->getWeekStart($startDate);
        $end = $this->getWeekEnd($endDate);

        while ($current->lte($end)) {
            $weekEnd = $this->getWeekEnd($current);
            
            $weeks->push([
                'week_start' => $current->copy(),
                'week_end' => $weekEnd->copy(),
                'label' => $current->format('M d') . ' - ' . $weekEnd->format('M d, Y')
            ]);

            $current->addWeek();
        }

        return $weeks;
    }

    /**
     * Check if an operator has any completed trips in a week
     */
    public function hasTripsInWeek(int $operatorId, Carbon $weekStart): bool
    {
        return $this->countTripsForWeek($operatorId, $weekStart) > 0;
    }

    /**
     * Get operators with completed trips in a specific week
     */
    public function getOperatorsWithTripsInWeek(Carbon $weekStart): Collection
    {
        $weekEnd = $weekStart->copy()->endOfWeek();

        return User::operators()->whereHas('trips', function ($query) use ($weekStart, $weekEnd) {
            $query->where('status', 'completed')
                  ->whereBetween('completed_at', [$weekStart, $weekEnd]);
        })->get();
    }
}