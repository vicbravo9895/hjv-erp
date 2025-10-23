<?php

namespace App\Services\Validation;

class ReservationResult
{
    public function __construct(
        public bool $success,
        public array $reservedItems = [],
        public array $failedItems = [],
        public string $reservationId = ''
    ) {}

    public static function success(array $reservedItems, string $reservationId = ''): self
    {
        return new self(true, $reservedItems, [], $reservationId);
    }

    public static function failure(array $failedItems): self
    {
        return new self(false, [], $failedItems);
    }

    public static function partial(array $reservedItems, array $failedItems, string $reservationId = ''): self
    {
        return new self(false, $reservedItems, $failedItems, $reservationId);
    }

    public function isFullSuccess(): bool
    {
        return $this->success && empty($this->failedItems);
    }

    public function isPartialSuccess(): bool
    {
        return !empty($this->reservedItems) && !empty($this->failedItems);
    }

    public function hasFailures(): bool
    {
        return !empty($this->failedItems);
    }

    public function getFormattedMessage(): string
    {
        if ($this->isFullSuccess()) {
            return '✅ Todos los artículos fueron reservados exitosamente.';
        }

        $message = '';

        if (!empty($this->reservedItems)) {
            $message .= '✅ Artículos reservados: ' . implode(', ', array_map(
                fn($item) => "{$item['name']} (x{$item['quantity']})",
                $this->reservedItems
            ));
        }

        if (!empty($this->failedItems)) {
            if ($message) {
                $message .= "\n\n";
            }
            $message .= '❌ Artículos no disponibles: ' . implode(', ', array_map(
                fn($item) => "{$item['name']} (solicitado: {$item['requested']}, disponible: {$item['available']})",
                $this->failedItems
            ));
        }

        return $message;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'reservedItems' => $this->reservedItems,
            'failedItems' => $this->failedItems,
            'reservationId' => $this->reservationId,
        ];
    }
}
