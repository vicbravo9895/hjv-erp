<?php

namespace App\Services\Validation;

use App\Models\SparePart;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StockValidationService
{
    /**
     * Temporary reservations storage (in production, use cache or database)
     */
    protected static array $reservations = [];

    /**
     * Validate if a part is available in the requested quantity.
     */
    public function validatePartAvailability(int $partId, float $quantity): ValidationResult
    {
        $part = SparePart::find($partId);

        if (!$part) {
            return ValidationResult::failure(['La pieza especificada no existe.']);
        }

        if ($quantity <= 0) {
            return ValidationResult::failure(['La cantidad debe ser mayor a 0.']);
        }

        $availableStock = $this->getAvailableStock($partId);

        if ($availableStock >= $quantity) {
            $result = ValidationResult::success();
            
            // Add warning if stock is getting low
            if ($availableStock - $quantity <= 5) {
                $result->addWarning(
                    "Stock bajo después de esta operación: {$part->name} tendrá " .
                    ($availableStock - $quantity) . " unidades restantes."
                );
            }

            return $result;
        }

        $result = ValidationResult::failure([
            "Stock insuficiente para {$part->name}. " .
            "Disponible: {$availableStock}, Solicitado: {$quantity}."
        ]);

        // Add suggestions
        if ($availableStock > 0) {
            $result->addSuggestion(
                "Puede reducir la cantidad a {$availableStock} unidades o menos."
            );
        }

        // Find alternative parts
        $alternatives = $this->findAlternativeParts($part);
        if ($alternatives->isNotEmpty()) {
            $result->addSuggestion(
                'Piezas alternativas disponibles: ' . 
                $alternatives->pluck('display_name')->join(', ')
            );
        }

        $result->addSuggestion('Crear una solicitud de compra para reabastecer el inventario.');

        return $result;
    }

    /**
     * Validate multiple parts availability.
     */
    public function validateMultiplePartsAvailability(array $parts): ValidationResult
    {
        $result = ValidationResult::success();
        $hasErrors = false;

        foreach ($parts as $partData) {
            $partId = $partData['part_id'] ?? $partData['id'] ?? null;
            $quantity = $partData['quantity'] ?? 0;

            if (!$partId) {
                continue;
            }

            $partValidation = $this->validatePartAvailability($partId, $quantity);

            if (!$partValidation->isValid) {
                $hasErrors = true;
                foreach ($partValidation->errors as $error) {
                    $result->addError($error);
                }
            }

            foreach ($partValidation->warnings as $warning) {
                $result->addWarning($warning);
            }

            foreach ($partValidation->suggestions as $suggestion) {
                $result->addSuggestion($suggestion);
            }
        }

        if ($hasErrors) {
            $result->isValid = false;
        }

        return $result;
    }

    /**
     * Reserve parts for a maintenance operation.
     */
    public function reserveParts(array $parts, ?string $reservationId = null): ReservationResult
    {
        $reservationId = $reservationId ?? $this->generateReservationId();
        $reservedItems = [];
        $failedItems = [];

        foreach ($parts as $partData) {
            $partId = $partData['part_id'] ?? $partData['id'] ?? null;
            $quantity = $partData['quantity'] ?? 0;

            if (!$partId) {
                continue;
            }

            $part = SparePart::find($partId);
            if (!$part) {
                $failedItems[] = [
                    'part_id' => $partId,
                    'name' => 'Pieza desconocida',
                    'requested' => $quantity,
                    'available' => 0,
                    'reason' => 'Pieza no encontrada',
                ];
                continue;
            }

            $availableStock = $this->getAvailableStock($partId);

            if ($availableStock >= $quantity) {
                // Reserve the part
                $this->addReservation($reservationId, $partId, $quantity);
                
                $reservedItems[] = [
                    'part_id' => $partId,
                    'name' => $part->name,
                    'quantity' => $quantity,
                    'unit_cost' => $part->unit_cost,
                    'total_cost' => $quantity * $part->unit_cost,
                ];
            } else {
                $failedItems[] = [
                    'part_id' => $partId,
                    'name' => $part->name,
                    'requested' => $quantity,
                    'available' => $availableStock,
                    'reason' => 'Stock insuficiente',
                ];
            }
        }

        if (empty($failedItems)) {
            return ReservationResult::success($reservedItems, $reservationId);
        } elseif (empty($reservedItems)) {
            return ReservationResult::failure($failedItems);
        } else {
            return ReservationResult::partial($reservedItems, $failedItems, $reservationId);
        }
    }

    /**
     * Release reserved parts.
     */
    public function releaseParts(string $reservationId): void
    {
        if (isset(self::$reservations[$reservationId])) {
            unset(self::$reservations[$reservationId]);
        }
    }

    /**
     * Commit reservation and reduce actual stock.
     */
    public function commitReservation(string $reservationId): bool
    {
        if (!isset(self::$reservations[$reservationId])) {
            return false;
        }

        $reservation = self::$reservations[$reservationId];
        $success = true;

        foreach ($reservation as $partId => $quantity) {
            $part = SparePart::find($partId);
            if ($part) {
                try {
                    $part->reduceStockWithValidation(
                        $quantity,
                        "Reservation {$reservationId} committed"
                    );
                } catch (\Exception $e) {
                    $success = false;
                    \Log::error("Failed to commit reservation for part {$partId}: " . $e->getMessage());
                }
            }
        }

        // Release the reservation after committing
        $this->releaseParts($reservationId);

        return $success;
    }

    /**
     * Get available stock considering reservations.
     */
    protected function getAvailableStock(int $partId): float
    {
        $part = SparePart::find($partId);
        if (!$part) {
            return 0;
        }

        $physicalStock = $part->stock_quantity;
        $reservedQuantity = $this->getReservedQuantity($partId);

        return max(0, $physicalStock - $reservedQuantity);
    }

    /**
     * Get total reserved quantity for a part.
     */
    protected function getReservedQuantity(int $partId): float
    {
        $total = 0;

        foreach (self::$reservations as $reservation) {
            if (isset($reservation[$partId])) {
                $total += $reservation[$partId];
            }
        }

        return $total;
    }

    /**
     * Add a reservation.
     */
    protected function addReservation(string $reservationId, int $partId, float $quantity): void
    {
        if (!isset(self::$reservations[$reservationId])) {
            self::$reservations[$reservationId] = [];
        }

        if (!isset(self::$reservations[$reservationId][$partId])) {
            self::$reservations[$reservationId][$partId] = 0;
        }

        self::$reservations[$reservationId][$partId] += $quantity;
    }

    /**
     * Generate a unique reservation ID.
     */
    protected function generateReservationId(): string
    {
        return 'RSV-' . strtoupper(Str::random(10)) . '-' . time();
    }

    /**
     * Find alternative parts based on similar characteristics.
     */
    protected function findAlternativeParts(SparePart $part): Collection
    {
        // Find parts with similar names or same brand that are in stock
        return SparePart::where('id', '!=', $part->id)
            ->where(function ($query) use ($part) {
                $query->where('brand', $part->brand)
                    ->orWhere('name', 'LIKE', '%' . $this->extractKeywords($part->name) . '%');
            })
            ->inStock()
            ->take(3)
            ->get();
    }

    /**
     * Extract keywords from part name for similarity search.
     */
    protected function extractKeywords(string $name): string
    {
        // Remove common words and extract main keywords
        $commonWords = ['de', 'para', 'con', 'sin', 'el', 'la', 'los', 'las'];
        $words = explode(' ', strtolower($name));
        $keywords = array_diff($words, $commonWords);
        
        return implode(' ', array_slice($keywords, 0, 2));
    }

    /**
     * Get stock status for a part with detailed information.
     */
    public function getStockStatus(int $partId): array
    {
        $part = SparePart::find($partId);

        if (!$part) {
            return [
                'exists' => false,
                'message' => 'Pieza no encontrada',
            ];
        }

        $availableStock = $this->getAvailableStock($partId);
        $reservedQuantity = $this->getReservedQuantity($partId);
        $alertLevel = $part->getLowStockAlertLevel();

        return [
            'exists' => true,
            'part_id' => $partId,
            'name' => $part->name,
            'physical_stock' => $part->stock_quantity,
            'reserved_quantity' => $reservedQuantity,
            'available_stock' => $availableStock,
            'alert_level' => $alertLevel,
            'unit_cost' => $part->unit_cost,
            'is_available' => $availableStock > 0,
            'estimated_days_until_stockout' => $part->getEstimatedDaysUntilStockOut(),
            'needs_reordering' => $part->needsReordering(),
        ];
    }

    /**
     * Get real-time stock information for multiple parts.
     */
    public function getMultipleStockStatus(array $partIds): array
    {
        $results = [];

        foreach ($partIds as $partId) {
            $results[$partId] = $this->getStockStatus($partId);
        }

        return $results;
    }

    /**
     * Clear all reservations (useful for testing or cleanup).
     */
    public function clearAllReservations(): void
    {
        self::$reservations = [];
    }

    /**
     * Get all active reservations.
     */
    public function getActiveReservations(): array
    {
        return self::$reservations;
    }
}
