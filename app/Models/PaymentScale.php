<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentScale extends Model
{
    protected $fillable = [
        'trips_count',
        'payment_amount',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
    ];

    /**
     * Get the payment amount for a specific number of trips
     */
    public static function getPaymentForTrips(int $tripsCount): ?float
    {
        $scale = static::where('trips_count', $tripsCount)->first();
        return $scale ? $scale->payment_amount : null;
    }

    /**
     * Get the closest payment scale for trips count (if exact match not found)
     */
    public static function getClosestPaymentForTrips(int $tripsCount): ?float
    {
        // First try exact match
        $exactMatch = static::getPaymentForTrips($tripsCount);
        if ($exactMatch !== null) {
            return $exactMatch;
        }

        // If no exact match, get the highest scale that's less than or equal to trips count
        $scale = static::where('trips_count', '<=', $tripsCount)
            ->orderBy('trips_count', 'desc')
            ->first();

        return $scale ? $scale->payment_amount : 0;
    }
}
