<?php

namespace App\Services;

use Carbon\Carbon;

class ErrorMessageService
{
    /**
     * Get stock shortage error message.
     */
    public function getStockShortageMessage(string $partName, int $available, int $requested): string
    {
        return "❌ Stock insuficiente para {$partName}. " .
               "Disponible: {$available}, Solicitado: {$requested}. " .
               "💡 Sugerencia: Reducir cantidad o solicitar reabastecimiento.";
    }

    /**
     * Get scheduling conflict error message.
     */
    public function getSchedulingConflictMessage(string $vehicleName, Carbon $conflictDate): string
    {
        return "⚠️ {$vehicleName} ya tiene un viaje programado el {$conflictDate->format('d/m/Y')}. " .
               "💡 Sugerencia: Seleccionar otro vehículo o modificar las fechas.";
    }

    /**
     * Get operator conflict error message.
     */
    public function getOperatorConflictMessage(string $operatorName, Carbon $conflictDate): string
    {
        return "⚠️ {$operatorName} ya tiene un viaje programado el {$conflictDate->format('d/m/Y')}. " .
               "💡 Sugerencia: Seleccionar otro operador o modificar las fechas.";
    }

    /**
     * Get vehicle unavailable error message.
     */
    public function getVehicleUnavailableMessage(string $vehicleName, string $status): string
    {
        $statusText = match($status) {
            'in_maintenance' => 'en mantenimiento',
            'in_trip' => 'en viaje',
            'out_of_service' => 'fuera de servicio',
            default => $status
        };

        return "❌ {$vehicleName} no está disponible (estado: {$statusText}). " .
               "💡 Sugerencia: Seleccionar un vehículo disponible.";
    }

    /**
     * Get operator unavailable error message.
     */
    public function getOperatorUnavailableMessage(string $operatorName, string $status): string
    {
        $statusText = match($status) {
            'inactive' => 'inactivo',
            'suspended' => 'suspendido',
            default => $status
        };

        return "❌ {$operatorName} no está disponible (estado: {$statusText}). " .
               "💡 Sugerencia: Seleccionar un operador activo.";
    }

    /**
     * Get alternative vehicles suggestion message.
     */
    public function getAlternativeVehiclesSuggestion(array $vehicleNames): string
    {
        if (empty($vehicleNames)) {
            return "⚠️ No hay vehículos alternativos disponibles en estas fechas.";
        }

        $vehicles = implode(', ', $vehicleNames);
        return "💡 Vehículos disponibles: {$vehicles}";
    }

    /**
     * Get alternative operators suggestion message.
     */
    public function getAlternativeOperatorsSuggestion(array $operatorNames): string
    {
        if (empty($operatorNames)) {
            return "⚠️ No hay operadores alternativos disponibles en estas fechas.";
        }

        $operators = implode(', ', $operatorNames);
        return "💡 Operadores disponibles: {$operators}";
    }

    /**
     * Get date range conflict message.
     */
    public function getDateRangeConflictMessage(Carbon $startDate, Carbon $endDate, int $conflictCount): string
    {
        return "⚠️ Se encontraron {$conflictCount} conflicto(s) de programación " .
               "entre {$startDate->format('d/m/Y')} y {$endDate->format('d/m/Y')}.";
    }

    /**
     * Get permission denied message.
     */
    public function getPermissionDeniedMessage(string $action, string $requiredRole): string
    {
        return "❌ No tiene permisos para {$action}. " .
               "Se requiere rol: {$requiredRole}. " .
               "💡 Contacte al administrador para solicitar acceso.";
    }

    /**
     * Get validation success message.
     */
    public function getValidationSuccessMessage(string $resourceType): string
    {
        return "✅ {$resourceType} validado correctamente. Todos los recursos están disponibles.";
    }

    /**
     * Format multiple errors into a single message.
     */
    public function formatMultipleErrors(array $errors): string
    {
        if (empty($errors)) {
            return '';
        }

        return implode("\n", array_map(fn($error) => "❌ {$error}", $errors));
    }

    /**
     * Format multiple warnings into a single message.
     */
    public function formatMultipleWarnings(array $warnings): string
    {
        if (empty($warnings)) {
            return '';
        }

        return implode("\n", array_map(fn($warning) => "⚠️ {$warning}", $warnings));
    }

    /**
     * Format multiple suggestions into a single message.
     */
    public function formatMultipleSuggestions(array $suggestions): string
    {
        if (empty($suggestions)) {
            return '';
        }

        return implode("\n", array_map(fn($suggestion) => "💡 {$suggestion}", $suggestions));
    }

    /**
     * Get comprehensive validation message with errors, warnings, and suggestions.
     */
    public function getComprehensiveValidationMessage(
        array $errors = [],
        array $warnings = [],
        array $suggestions = []
    ): string {
        $message = '';

        if (!empty($errors)) {
            $message .= $this->formatMultipleErrors($errors);
        }

        if (!empty($warnings)) {
            if ($message) {
                $message .= "\n\n";
            }
            $message .= $this->formatMultipleWarnings($warnings);
        }

        if (!empty($suggestions)) {
            if ($message) {
                $message .= "\n\n";
            }
            $message .= $this->formatMultipleSuggestions($suggestions);
        }

        return $message;
    }
}
