<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\Trailer;
use App\Models\User;
use App\Models\Trip;
use App\Services\Validation\TripValidationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class VehicleAssignmentService
{
    protected VehicleStatusService $statusService;
    protected TripValidationService $validationService;

    public function __construct(
        VehicleStatusService $statusService,
        TripValidationService $validationService
    ) {
        $this->statusService = $statusService;
        $this->validationService = $validationService;
    }

    /**
     * Validate if a vehicle can be assigned to a trip.
     */
    public function canAssignVehicle(Vehicle $vehicle, ?\DateTime $startDate = null, ?\DateTime $endDate = null, ?int $excludeTripId = null): array
    {
        $errors = [];

        // Check if vehicle is available
        if (!$vehicle->isAvailable()) {
            $errors[] = "El vehículo {$vehicle->display_name} no está disponible (estatus: {$vehicle->status})";
        }

        // Use TripValidationService for date-based validation
        if ($startDate && $endDate) {
            $start = Carbon::instance($startDate);
            $end = Carbon::instance($endDate);
            
            $validation = $this->validationService->validateVehicleAvailability(
                $vehicle->id,
                $start,
                $end,
                $excludeTripId
            );

            if (!$validation->isValid) {
                $errors = array_merge($errors, $validation->errors);
            }
        }

        return [
            'can_assign' => empty($errors),
            'errors' => $errors,
            'warnings' => $validation->warnings ?? [],
            'suggestions' => $validation->suggestions ?? []
        ];
    }

    /**
     * Validate if a trailer can be assigned to a trip.
     */
    public function canAssignTrailer(Trailer $trailer, ?\DateTime $startDate = null, ?\DateTime $endDate = null, ?int $excludeTripId = null): array
    {
        $errors = [];
        $warnings = [];

        // Check if trailer is available
        if (!$trailer->isAvailable()) {
            $errors[] = "El trailer {$trailer->display_name} no está disponible (estatus: {$trailer->status})";
        }

        // Check for overlapping trips if dates are provided
        if ($startDate && $endDate) {
            $overlappingTrips = $this->getOverlappingTrailerTrips($trailer, $startDate, $endDate, $excludeTripId);
            if ($overlappingTrips->count() > 0) {
                $errors[] = "El trailer {$trailer->display_name} tiene viajes que se superponen en las fechas seleccionadas";
                
                // Add detailed conflict information
                foreach ($overlappingTrips as $conflict) {
                    $warnings[] = "Conflicto: {$conflict->origin} → {$conflict->destination} " .
                                 "({$conflict->start_date->format('d/m/Y')} - {$conflict->end_date->format('d/m/Y')})";
                }
            }
        }

        return [
            'can_assign' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'suggestions' => []
        ];
    }

    /**
     * Validate if an operator can be assigned to a trip.
     */
    public function canAssignOperator($operator, ?\DateTime $startDate = null, ?\DateTime $endDate = null, ?int $excludeTripId = null): array
    {
        $errors = [];

        // Support both Operator and User models for backward compatibility
        $operatorId = $operator instanceof User ? $operator->id : $operator->id;
        $operatorName = $operator instanceof User ? $operator->name : $operator->name;
        $isActive = $operator instanceof User ? $operator->isActive() : $operator->isActive();

        // Check if operator is active
        if (!$isActive) {
            $errors[] = "El operador {$operatorName} no está activo (estatus: {$operator->status})";
        }

        // Use TripValidationService for date-based validation
        if ($startDate && $endDate) {
            $start = Carbon::instance($startDate);
            $end = Carbon::instance($endDate);
            
            $validation = $this->validationService->validateOperatorAvailability(
                $operatorId,
                $start,
                $end,
                $excludeTripId
            );

            if (!$validation->isValid) {
                $errors = array_merge($errors, $validation->errors);
            }
        }

        return [
            'can_assign' => empty($errors),
            'errors' => $errors,
            'warnings' => $validation->warnings ?? [],
            'suggestions' => $validation->suggestions ?? []
        ];
    }

    /**
     * Validate complete trip assignment (vehicle, trailer, operator).
     */
    public function validateTripAssignment(
        Vehicle $vehicle,
        ?Trailer $trailer,
        $operator,
        \DateTime $startDate,
        \DateTime $endDate,
        ?int $excludeTripId = null
    ): array {
        $allErrors = [];
        $allWarnings = [];
        $allSuggestions = [];

        // Validate vehicle assignment
        $vehicleValidation = $this->canAssignVehicle($vehicle, $startDate, $endDate, $excludeTripId);
        if (!$vehicleValidation['can_assign']) {
            $allErrors = array_merge($allErrors, $vehicleValidation['errors']);
        }
        $allWarnings = array_merge($allWarnings, $vehicleValidation['warnings'] ?? []);
        $allSuggestions = array_merge($allSuggestions, $vehicleValidation['suggestions'] ?? []);

        // Validate trailer assignment if provided
        if ($trailer) {
            $trailerValidation = $this->canAssignTrailer($trailer, $startDate, $endDate, $excludeTripId);
            if (!$trailerValidation['can_assign']) {
                $allErrors = array_merge($allErrors, $trailerValidation['errors']);
            }
            $allWarnings = array_merge($allWarnings, $trailerValidation['warnings'] ?? []);
            $allSuggestions = array_merge($allSuggestions, $trailerValidation['suggestions'] ?? []);
        }

        // Validate operator assignment
        $operatorValidation = $this->canAssignOperator($operator, $startDate, $endDate, $excludeTripId);
        if (!$operatorValidation['can_assign']) {
            $allErrors = array_merge($allErrors, $operatorValidation['errors']);
        }
        $allWarnings = array_merge($allWarnings, $operatorValidation['warnings'] ?? []);
        $allSuggestions = array_merge($allSuggestions, $operatorValidation['suggestions'] ?? []);

        return [
            'can_assign' => empty($allErrors),
            'errors' => $allErrors,
            'warnings' => $allWarnings,
            'suggestions' => $allSuggestions
        ];
    }

    /**
     * Assign resources to a trip and update their statuses.
     */
    public function assignToTrip(Trip $trip): array
    {
        $errors = [];

        try {
            DB::beginTransaction();

            // Validate assignment before proceeding
            $validation = $this->validateTripAssignment(
                $trip->truck,
                $trip->trailer,
                $trip->operator,
                new \DateTime($trip->start_date),
                new \DateTime($trip->end_date),
                $trip->id
            );

            if (!$validation['can_assign']) {
                DB::rollBack();
                return [
                    'success' => false,
                    'errors' => $validation['errors'],
                    'warnings' => $validation['warnings'] ?? [],
                    'suggestions' => $validation['suggestions'] ?? []
                ];
            }

            // Update vehicle status
            if ($trip->truck && !$this->statusService->markVehicleInTrip($trip->truck)) {
                $errors[] = "No se pudo actualizar el estatus del vehículo";
            }

            // Update trailer status if present
            if ($trip->trailer && !$this->statusService->markTrailerInTrip($trip->trailer)) {
                $errors[] = "No se pudo actualizar el estatus del trailer";
            }

            if (!empty($errors)) {
                DB::rollBack();
                return [
                    'success' => false,
                    'errors' => $errors
                ];
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Recursos asignados correctamente al viaje'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'errors' => ['Error interno: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Release resources from a completed trip.
     */
    public function releaseFromTrip(Trip $trip): array
    {
        try {
            DB::beginTransaction();

            // Update vehicle status to available
            if ($trip->truck) {
                $this->statusService->markVehicleAvailable($trip->truck);
            }

            // Update trailer status to available if present
            if ($trip->trailer) {
                $this->statusService->markTrailerAvailable($trip->trailer);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Recursos liberados correctamente'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'errors' => ['Error interno: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Get available resources for assignment.
     */
    public function getAvailableResources(): array
    {
        return [
            'vehicles' => $this->statusService->getAvailableVehicles(),
            'trailers' => $this->statusService->getAvailableTrailers(),
            'operators' => User::activeOperators()->get(),
        ];
    }

    /**
     * Get overlapping trips for a vehicle in a date range.
     */
    protected function getOverlappingTrips(Vehicle $vehicle, \DateTime $startDate, \DateTime $endDate): Collection
    {
        return $vehicle->trips()
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                    });
            })
            ->whereIn('status', ['pending', 'in_progress'])
            ->get();
    }

    /**
     * Get overlapping trips for a trailer in a date range.
     */
    protected function getOverlappingTrailerTrips(Trailer $trailer, \DateTime $startDate, \DateTime $endDate, ?int $excludeTripId = null): Collection
    {
        $query = $trailer->trips()
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                    });
            })
            ->whereIn('status', ['pending', 'in_progress', 'planned']);

        if ($excludeTripId) {
            $query->where('id', '!=', $excludeTripId);
        }

        return $query->get();
    }

    /**
     * Get overlapping trips for an operator in a date range.
     */
    protected function getOverlappingOperatorTrips(User $operator, \DateTime $startDate, \DateTime $endDate): Collection
    {
        return $operator->trips()
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<=', $startDate)
                          ->where('end_date', '>=', $endDate);
                    });
            })
            ->whereIn('status', ['pending', 'in_progress'])
            ->get();
    }

    /**
     * Check if an operator has any active trips.
     */
    protected function operatorHasActiveTrips(User $operator): bool
    {
        return $operator->trips()
            ->whereIn('status', ['pending', 'in_progress'])
            ->exists();
    }
}