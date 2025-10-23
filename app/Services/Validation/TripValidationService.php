<?php

namespace App\Services\Validation;

use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\User;
use App\Services\ErrorMessageService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TripValidationService
{
    protected ErrorMessageService $errorMessageService;

    public function __construct(ErrorMessageService $errorMessageService)
    {
        $this->errorMessageService = $errorMessageService;
    }

    /**
     * Validate if a vehicle is available for the specified date range.
     */
    public function validateVehicleAvailability(
        int $vehicleId,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeTripId = null
    ): ValidationResult {
        $vehicle = Vehicle::find($vehicleId);

        if (!$vehicle) {
            return ValidationResult::failure(['El vehículo especificado no existe.']);
        }

        $conflicts = $this->findVehicleConflicts($vehicleId, $startDate, $endDate, $excludeTripId);

        if ($conflicts->isEmpty()) {
            return ValidationResult::success();
        }

        // Use ErrorMessageService for consistent messaging
        $result = ValidationResult::failure([
            $this->errorMessageService->getDateRangeConflictMessage($startDate, $endDate, $conflicts->count()) .
            " (Vehículo: {$vehicle->display_name})"
        ]);

        // Add detailed conflict information
        foreach ($conflicts as $conflict) {
            $result->addWarning(
                "Conflicto: {$conflict->origin} → {$conflict->destination} " .
                "({$conflict->start_date->format('d/m/Y')} - {$conflict->end_date->format('d/m/Y')})"
            );
        }

        // Add suggestions for alternative vehicles
        $alternatives = $this->findAlternativeVehicles($startDate, $endDate, $excludeTripId);
        $alternativeNames = $alternatives->pluck('display_name')->toArray();
        $result->addSuggestion(
            $this->errorMessageService->getAlternativeVehiclesSuggestion($alternativeNames)
        );

        return $result;
    }

    /**
     * Validate if an operator is available for the specified date range.
     */
    public function validateOperatorAvailability(
        int $operatorId,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeTripId = null
    ): ValidationResult {
        $operator = User::find($operatorId);

        if (!$operator) {
            return ValidationResult::failure(['El operador especificado no existe.']);
        }

        if (!$operator->isOperator()) {
            return ValidationResult::failure(['El usuario seleccionado no es un operador.']);
        }

        if (!$operator->isActive()) {
            return ValidationResult::failure([
                "El operador {$operator->name} no está activo (estado: {$operator->status})."
            ]);
        }

        $conflicts = $this->findOperatorConflicts($operatorId, $startDate, $endDate, $excludeTripId);

        if ($conflicts->isEmpty()) {
            return ValidationResult::success();
        }

        // Use ErrorMessageService for consistent messaging
        $result = ValidationResult::failure([
            $this->errorMessageService->getDateRangeConflictMessage($startDate, $endDate, $conflicts->count()) .
            " (Operador: {$operator->name})"
        ]);

        // Add detailed conflict information
        foreach ($conflicts as $conflict) {
            $result->addWarning(
                "Conflicto: {$conflict->origin} → {$conflict->destination} " .
                "({$conflict->start_date->format('d/m/Y')} - {$conflict->end_date->format('d/m/Y')})"
            );
        }

        // Add suggestions for alternative operators
        $alternatives = $this->findAlternativeOperators($startDate, $endDate, $excludeTripId);
        $alternativeNames = $alternatives->pluck('name')->toArray();
        $result->addSuggestion(
            $this->errorMessageService->getAlternativeOperatorsSuggestion($alternativeNames)
        );

        return $result;
    }

    /**
     * Find vehicle scheduling conflicts for the specified date range.
     */
    protected function findVehicleConflicts(
        int $vehicleId,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeTripId = null
    ): Collection {
        $query = Trip::where('truck_id', $vehicleId)
            ->whereIn('status', ['planned', 'in_progress'])
            ->where(function ($q) use ($startDate, $endDate) {
                // Check for any date overlap
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        // Check if the existing trip completely encompasses the new date range
                        $q2->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            });

        if ($excludeTripId) {
            $query->where('id', '!=', $excludeTripId);
        }

        return $query->get();
    }

    /**
     * Find operator scheduling conflicts for the specified date range.
     */
    protected function findOperatorConflicts(
        int $operatorId,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeTripId = null
    ): Collection {
        $query = Trip::where('operator_id', $operatorId)
            ->whereIn('status', ['planned', 'in_progress'])
            ->where(function ($q) use ($startDate, $endDate) {
                // Check for any date overlap
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        // Check if the existing trip completely encompasses the new date range
                        $q2->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            });

        if ($excludeTripId) {
            $query->where('id', '!=', $excludeTripId);
        }

        return $query->get();
    }

    /**
     * Find alternative vehicles available for the specified date range.
     */
    protected function findAlternativeVehicles(
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeTripId = null
    ): Collection {
        // Get all vehicles
        $allVehicles = Vehicle::all();

        // Filter out vehicles with conflicts
        return $allVehicles->filter(function ($vehicle) use ($startDate, $endDate, $excludeTripId) {
            $conflicts = $this->findVehicleConflicts($vehicle->id, $startDate, $endDate, $excludeTripId);
            return $conflicts->isEmpty();
        })->take(5); // Limit to 5 suggestions
    }

    /**
     * Find alternative operators available for the specified date range.
     */
    protected function findAlternativeOperators(
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeTripId = null
    ): Collection {
        // Get all active operators
        $allOperators = User::activeOperators()->get();

        // Filter out operators with conflicts
        return $allOperators->filter(function ($operator) use ($startDate, $endDate, $excludeTripId) {
            $conflicts = $this->findOperatorConflicts($operator->id, $startDate, $endDate, $excludeTripId);
            return $conflicts->isEmpty();
        })->take(5); // Limit to 5 suggestions
    }

    /**
     * Validate complete trip assignment (both vehicle and operator).
     */
    public function validateTripAssignment(
        int $vehicleId,
        int $operatorId,
        Carbon $startDate,
        Carbon $endDate,
        ?int $excludeTripId = null
    ): ValidationResult {
        $vehicleValidation = $this->validateVehicleAvailability($vehicleId, $startDate, $endDate, $excludeTripId);
        $operatorValidation = $this->validateOperatorAvailability($operatorId, $startDate, $endDate, $excludeTripId);

        // If both are valid, return success
        if ($vehicleValidation->isValid && $operatorValidation->isValid) {
            return ValidationResult::success();
        }

        // Combine errors, warnings, and suggestions
        $result = new ValidationResult(false);

        foreach ($vehicleValidation->errors as $error) {
            $result->addError($error);
        }

        foreach ($operatorValidation->errors as $error) {
            $result->addError($error);
        }

        foreach ($vehicleValidation->warnings as $warning) {
            $result->addWarning($warning);
        }

        foreach ($operatorValidation->warnings as $warning) {
            $result->addWarning($warning);
        }

        foreach ($vehicleValidation->suggestions as $suggestion) {
            $result->addSuggestion($suggestion);
        }

        foreach ($operatorValidation->suggestions as $suggestion) {
            $result->addSuggestion($suggestion);
        }

        return $result;
    }
}
