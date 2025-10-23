<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\Trailer;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Collection;

class VehicleStatusService
{
    /**
     * Update vehicle status to 'in_trip' when assigned to a trip.
     */
    public function markVehicleInTrip(Vehicle $vehicle): bool
    {
        if (!$vehicle->isAvailable()) {
            return false;
        }

        return $vehicle->update(['status' => 'in_trip']);
    }

    /**
     * Update trailer status to 'in_trip' when assigned to a trip.
     */
    public function markTrailerInTrip(Trailer $trailer): bool
    {
        if (!$trailer->isAvailable()) {
            return false;
        }

        return $trailer->update(['status' => 'in_trip']);
    }

    /**
     * Update vehicle status to 'available' when trip is completed.
     */
    public function markVehicleAvailable(Vehicle $vehicle): bool
    {
        return $vehicle->update(['status' => 'available']);
    }

    /**
     * Update trailer status to 'available' when trip is completed.
     */
    public function markTrailerAvailable(Trailer $trailer): bool
    {
        return $trailer->update(['status' => 'available']);
    }

    /**
     * Update vehicle status to maintenance.
     */
    public function markVehicleInMaintenance(Vehicle $vehicle): bool
    {
        // Only allow if vehicle is available or already in maintenance
        if (!in_array($vehicle->status, ['available', 'maintenance'])) {
            return false;
        }

        return $vehicle->update(['status' => 'maintenance']);
    }

    /**
     * Update trailer status to maintenance.
     */
    public function markTrailerInMaintenance(Trailer $trailer): bool
    {
        // Only allow if trailer is available or already in maintenance
        if (!in_array($trailer->status, ['available', 'maintenance'])) {
            return false;
        }

        return $trailer->update(['status' => 'maintenance']);
    }

    /**
     * Update vehicle status to out of service.
     */
    public function markVehicleOutOfService(Vehicle $vehicle): bool
    {
        // Only allow if vehicle is not currently in trip
        if ($vehicle->status === 'in_trip') {
            return false;
        }

        return $vehicle->update(['status' => 'out_of_service']);
    }

    /**
     * Update trailer status to out of service.
     */
    public function markTrailerOutOfService(Trailer $trailer): bool
    {
        // Only allow if trailer is not currently in trip
        if ($trailer->status === 'in_trip') {
            return false;
        }

        return $trailer->update(['status' => 'out_of_service']);
    }

    /**
     * Get all available vehicles for assignment.
     */
    public function getAvailableVehicles(): Collection
    {
        return Vehicle::available()->get();
    }

    /**
     * Get all available trailers for assignment.
     */
    public function getAvailableTrailers(): Collection
    {
        return Trailer::available()->get();
    }

    /**
     * Check if a vehicle has any active trips.
     */
    public function hasActiveTrips(Vehicle $vehicle): bool
    {
        return $vehicle->trips()
            ->whereIn('status', ['planned', 'in_progress'])
            ->exists();
    }

    /**
     * Check if a trailer has any active trips.
     */
    public function trailerHasActiveTrips(Trailer $trailer): bool
    {
        return $trailer->trips()
            ->whereIn('status', ['planned', 'in_progress'])
            ->exists();
    }

    /**
     * Automatically update vehicle statuses based on trip completion.
     */
    public function syncVehicleStatusesFromTrips(): array
    {
        $updated = ['vehicles' => 0, 'trailers' => 0];

        // Get all vehicles that should be available (no active trips)
        $vehiclesInTrip = Vehicle::inTrip()->get();
        foreach ($vehiclesInTrip as $vehicle) {
            if (!$this->hasActiveTrips($vehicle)) {
                $this->markVehicleAvailable($vehicle);
                $updated['vehicles']++;
            }
        }

        // Get all trailers that should be available (no active trips)
        $trailersInTrip = Trailer::inTrip()->get();
        foreach ($trailersInTrip as $trailer) {
            if (!$this->trailerHasActiveTrips($trailer)) {
                $this->markTrailerAvailable($trailer);
                $updated['trailers']++;
            }
        }

        return $updated;
    }

    /**
     * Get vehicle status statistics.
     */
    public function getVehicleStatusStats(): array
    {
        return [
            'available' => Vehicle::where('status', 'available')->count(),
            'in_trip' => Vehicle::where('status', 'in_trip')->count(),
            'maintenance' => Vehicle::where('status', 'maintenance')->count(),
            'out_of_service' => Vehicle::where('status', 'out_of_service')->count(),
            'total' => Vehicle::count(),
        ];
    }

    /**
     * Get trailer status statistics.
     */
    public function getTrailerStatusStats(): array
    {
        return [
            'available' => Trailer::where('status', 'available')->count(),
            'in_trip' => Trailer::where('status', 'in_trip')->count(),
            'maintenance' => Trailer::where('status', 'maintenance')->count(),
            'out_of_service' => Trailer::where('status', 'out_of_service')->count(),
            'total' => Trailer::count(),
        ];
    }
}