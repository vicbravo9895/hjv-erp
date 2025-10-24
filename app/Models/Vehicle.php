<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'vin',
        'serial_number',
        'name',
        'unit_number',
        'plate',
        'license_plate',
        'make',
        'model',
        'year',
        'status',
        'last_lat',
        'last_lng',
        'formatted_location',
        'last_location_at',
        'last_odometer_km',
        'last_fuel_percent',
        'last_engine_state',
        'last_speed_mph',
        'current_driver_external_id',
        'current_driver_name',
        'esn',
        'camera_serial',
        'gateway_model',
        'gateway_serial',
        'vehicle_type',
        'regulation_mode',
        'gross_vehicle_weight',
        'notes',
        'external_ids',
        'tags',
        'attributes',
        'sensor_configuration',
        'static_assigned_driver_id',
        'static_assigned_driver_name',
        'synced_at',
        'raw_snapshot',
    ];

    protected $casts = [
        'last_lat' => 'decimal:8',
        'last_lng' => 'decimal:8',
        'last_location_at' => 'datetime',
        'last_odometer_km' => 'decimal:2',
        'last_fuel_percent' => 'decimal:2',
        'last_speed_mph' => 'decimal:2',
        'synced_at' => 'datetime',
        'raw_snapshot' => 'array',
        'year' => 'integer',
        'external_ids' => 'array',
        'tags' => 'array',
        'attributes' => 'array',
        'sensor_configuration' => 'array',
        'gross_vehicle_weight' => 'integer',
    ];

    /**
     * Get the trips for this vehicle.
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class, 'truck_id');
    }

    /**
     * Scope to get available vehicles.
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Scope to get vehicles in trip.
     */
    public function scopeInTrip($query)
    {
        return $query->where('status', 'in_trip');
    }

    /**
     * Check if vehicle is available for assignment.
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    /**
     * Get the maintenance records for this vehicle.
     */
    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class, 'vehicle_id')
            ->where('vehicle_type', 'App\\Models\\Vehicle');
    }

    /**
     * Get the display name for the vehicle.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->unit_number ? "#{$this->unit_number} - {$this->name}" : $this->name;
    }
}
