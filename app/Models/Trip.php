<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'origin',
        'destination',
        'start_date',
        'end_date',
        'truck_id',
        'trailer_id',
        'operator_id',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the vehicle (truck) assigned to this trip.
     */
    public function truck(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'truck_id');
    }

    /**
     * Get the trailer assigned to this trip.
     */
    public function trailer(): BelongsTo
    {
        return $this->belongsTo(Trailer::class, 'trailer_id');
    }

    /**
     * Get the operator assigned to this trip.
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    /**
     * Get the costs associated with this trip.
     */
    public function costs(): HasMany
    {
        return $this->hasMany(TripCost::class, 'trip_id');
    }

    /**
     * Get the travel expenses associated with this trip.
     */
    public function travelExpenses(): HasMany
    {
        return $this->hasMany(TravelExpense::class, 'trip_id');
    }

    /**
     * Scope to get active trips.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['planned', 'in_progress']);
    }

    /**
     * Scope to get completed trips.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get trips in progress.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Check if trip is active (planned or in progress).
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['planned', 'in_progress']);
    }

    /**
     * Check if trip is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Get the display name for the trip.
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->origin} → {$this->destination} ({$this->start_date->format('Y-m-d')})";
    }

    /**
     * Get the total cost of the trip.
     */
    public function getTotalCostAttribute(): float
    {
        return (float) $this->costs()->sum('amount');
    }

    /**
     * Get diesel costs for this trip.
     */
    public function getDieselCostsAttribute(): float
    {
        return (float) $this->costs()->diesel()->sum('amount');
    }

    /**
     * Get toll costs for this trip.
     */
    public function getTollCostsAttribute(): float
    {
        return (float) $this->costs()->tolls()->sum('amount');
    }

    /**
     * Get maneuver costs for this trip.
     */
    public function getManeuverCostsAttribute(): float
    {
        return (float) $this->costs()->maneuvers()->sum('amount');
    }
}
