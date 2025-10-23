<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trailer extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'name',
        'asset_number',
        'plate',
        'type',
        'status',
        'last_lat',
        'last_lng',
        'formatted_location',
        'last_location_at',
        'last_speed_mph',
        'last_heading_degrees',
        'synced_at',
        'raw_snapshot',
    ];

    protected $casts = [
        'last_lat' => 'decimal:8',
        'last_lng' => 'decimal:8',
        'last_location_at' => 'datetime',
        'last_speed_mph' => 'decimal:2',
        'last_heading_degrees' => 'decimal:2',
        'synced_at' => 'datetime',
        'raw_snapshot' => 'array',
    ];

    /**
     * Get the trips for this trailer.
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class, 'trailer_id');
    }

    /**
     * Scope to get available trailers.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Scope to get trailers in trip.
     */
    public function scopeInTrip($query)
    {
        return $query->where('status', 'in_trip');
    }

    /**
     * Check if trailer is available for assignment.
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    /**
     * Get the maintenance records for this trailer.
     */
    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class, 'vehicle_id')
            ->where('vehicle_type', 'App\\Models\\Trailer');
    }

    /**
     * Get the display name for the trailer.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->asset_number ? "#{$this->asset_number} - {$this->name}" : $this->name;
    }
}
