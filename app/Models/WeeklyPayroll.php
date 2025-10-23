<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class WeeklyPayroll extends Model
{
    protected $fillable = [
        'operator_id',
        'week_start',
        'week_end',
        'trips_count',
        'base_payment',
        'adjustments',
        'total_payment',
    ];

    protected $casts = [
        'week_start' => 'date',
        'week_end' => 'date',
        'base_payment' => 'decimal:2',
        'adjustments' => 'decimal:2',
        'total_payment' => 'decimal:2',
    ];

    /**
     * Get the operator that owns the payroll record
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    /**
     * Calculate total payment including adjustments
     */
    public function calculateTotalPayment(): float
    {
        return $this->base_payment + $this->adjustments;
    }

    /**
     * Update the total payment based on base payment and adjustments
     */
    public function updateTotalPayment(): void
    {
        $this->total_payment = $this->calculateTotalPayment();
        $this->save();
    }

    /**
     * Get week range as formatted string
     */
    public function getWeekRangeAttribute(): string
    {
        return $this->week_start->format('M d') . ' - ' . $this->week_end->format('M d, Y');
    }

    /**
     * Scope to filter by operator
     */
    public function scopeForOperator($query, int $operatorId)
    {
        return $query->where('operator_id', $operatorId);
    }

    /**
     * Scope to filter by week containing a specific date
     */
    public function scopeForWeekContaining($query, Carbon $date)
    {
        return $query->where('week_start', '<=', $date)
                    ->where('week_end', '>=', $date);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeForPeriod($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('week_start', [$startDate, $endDate])
              ->orWhereBetween('week_end', [$startDate, $endDate])
              ->orWhere(function ($q2) use ($startDate, $endDate) {
                  $q2->where('week_start', '<=', $startDate)
                     ->where('week_end', '>=', $endDate);
              });
        });
    }
}
