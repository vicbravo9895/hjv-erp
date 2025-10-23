<?php

namespace App\Services;

use App\Models\PaymentScale;
use App\Models\WeeklyPayroll;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PaymentCalculationService
{
    protected WeeklyTripCountService $tripCountService;

    public function __construct(WeeklyTripCountService $tripCountService)
    {
        $this->tripCountService = $tripCountService;
    }

    /**
     * Calculate base payment for a given number of trips
     */
    public function calculateBasePayment(int $tripsCount): float
    {
        return PaymentScale::getClosestPaymentForTrips($tripsCount) ?? 0;
    }

    /**
     * Calculate total payment including adjustments
     */
    public function calculateTotalPayment(float $basePayment, float $adjustments = 0): float
    {
        return $basePayment + $adjustments;
    }

    /**
     * Create or update weekly payroll for an operator
     */
    public function calculateWeeklyPayroll(int $operatorId, Carbon $weekStart, float $adjustments = 0): WeeklyPayroll
    {
        $weekEnd = $this->tripCountService->getWeekEnd($weekStart);
        $tripsCount = $this->tripCountService->countTripsForWeek($operatorId, $weekStart);
        $basePayment = $this->calculateBasePayment($tripsCount);
        $totalPayment = $this->calculateTotalPayment($basePayment, $adjustments);

        return WeeklyPayroll::updateOrCreate(
            [
                'operator_id' => $operatorId,
                'week_start' => $weekStart->toDateString(),
            ],
            [
                'week_end' => $weekEnd->toDateString(),
                'trips_count' => $tripsCount,
                'base_payment' => $basePayment,
                'adjustments' => $adjustments,
                'total_payment' => $totalPayment,
            ]
        );
    }

    /**
     * Calculate payroll for all operators for a specific week
     */
    public function calculateWeeklyPayrollForAllOperators(Carbon $weekStart): Collection
    {
        $operators = $this->tripCountService->getOperatorsWithTripsInWeek($weekStart);
        $payrolls = collect();

        foreach ($operators as $operator) {
            $payroll = $this->calculateWeeklyPayroll($operator->id, $weekStart);
            $payrolls->push($payroll);
        }

        return $payrolls;
    }

    /**
     * Apply manual adjustment to existing payroll
     */
    public function applyAdjustment(WeeklyPayroll $payroll, float $adjustmentAmount, ?string $reason = null): WeeklyPayroll
    {
        $payroll->adjustments += $adjustmentAmount;
        $payroll->total_payment = $this->calculateTotalPayment($payroll->base_payment, $payroll->adjustments);
        $payroll->save();

        return $payroll;
    }

    /**
     * Set manual adjustment (replace existing adjustments)
     */
    public function setAdjustment(WeeklyPayroll $payroll, float $adjustmentAmount): WeeklyPayroll
    {
        $payroll->adjustments = $adjustmentAmount;
        $payroll->total_payment = $this->calculateTotalPayment($payroll->base_payment, $payroll->adjustments);
        $payroll->save();

        return $payroll;
    }

    /**
     * Get payment breakdown for an operator's week
     */
    public function getPaymentBreakdown(int $operatorId, Carbon $weekStart): array
    {
        $tripsCount = $this->tripCountService->countTripsForWeek($operatorId, $weekStart);
        $basePayment = $this->calculateBasePayment($tripsCount);
        $trips = $this->tripCountService->getTripsForWeek($operatorId, $weekStart);

        // Get existing payroll if it exists
        $payroll = WeeklyPayroll::where('operator_id', $operatorId)
            ->where('week_start', $weekStart->toDateString())
            ->first();

        $adjustments = $payroll ? $payroll->adjustments : 0;
        $totalPayment = $this->calculateTotalPayment($basePayment, $adjustments);

        return [
            'operator_id' => $operatorId,
            'week_start' => $weekStart,
            'week_end' => $this->tripCountService->getWeekEnd($weekStart),
            'trips_count' => $tripsCount,
            'base_payment' => $basePayment,
            'adjustments' => $adjustments,
            'total_payment' => $totalPayment,
            'trips' => $trips,
            'payroll_exists' => $payroll !== null,
        ];
    }

    /**
     * Calculate payroll summary for a period
     */
    public function calculatePayrollSummary(Carbon $startDate, Carbon $endDate): array
    {
        $payrolls = WeeklyPayroll::forPeriod($startDate, $endDate)
            ->with('operator')
            ->get();

        $totalPayments = $payrolls->sum('total_payment');
        $totalBasePayments = $payrolls->sum('base_payment');
        $totalAdjustments = $payrolls->sum('adjustments');
        $totalTrips = $payrolls->sum('trips_count');
        $operatorCount = $payrolls->pluck('operator_id')->unique()->count();

        return [
            'period_start' => $startDate,
            'period_end' => $endDate,
            'total_payments' => $totalPayments,
            'total_base_payments' => $totalBasePayments,
            'total_adjustments' => $totalAdjustments,
            'total_trips' => $totalTrips,
            'operator_count' => $operatorCount,
            'average_payment_per_operator' => $operatorCount > 0 ? $totalPayments / $operatorCount : 0,
            'payrolls' => $payrolls,
        ];
    }

    /**
     * Recalculate existing payroll based on current trips and payment scale
     */
    public function recalculatePayroll(WeeklyPayroll $payroll): WeeklyPayroll
    {
        $tripsCount = $this->tripCountService->countTripsForWeek(
            $payroll->operator_id, 
            Carbon::parse($payroll->week_start)
        );
        
        $basePayment = $this->calculateBasePayment($tripsCount);
        $totalPayment = $this->calculateTotalPayment($basePayment, $payroll->adjustments);

        $payroll->update([
            'trips_count' => $tripsCount,
            'base_payment' => $basePayment,
            'total_payment' => $totalPayment,
        ]);

        return $payroll;
    }

    /**
     * Get available payment scales
     */
    public function getPaymentScales(): Collection
    {
        return PaymentScale::orderBy('trips_count')->get();
    }

    /**
     * Validate if a payment scale exists for the given trips count
     */
    public function hasPaymentScaleForTrips(int $tripsCount): bool
    {
        return PaymentScale::where('trips_count', $tripsCount)->exists();
    }
}