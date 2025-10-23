<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAudit extends Model
{
    protected $fillable = [
        'spare_part_id',
        'change_type',
        'quantity_change',
        'previous_stock',
        'new_stock',
        'reference_type',
        'reference_id',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'quantity_change' => 'decimal:2',
        'previous_stock' => 'integer',
        'new_stock' => 'integer',
    ];

    /**
     * Change type constants.
     */
    public const CHANGE_TYPE_PRODUCT_REQUEST_RECEIVED = 'product_request_received';
    public const CHANGE_TYPE_PRODUCT_REQUEST_REVERSED = 'product_request_reversed';
    public const CHANGE_TYPE_MAINTENANCE_USAGE = 'maintenance_usage';
    public const CHANGE_TYPE_MANUAL_ADJUSTMENT = 'manual_adjustment';

    /**
     * Get the spare part associated with this audit entry.
     */
    public function sparePart(): BelongsTo
    {
        return $this->belongsTo(SparePart::class);
    }

    /**
     * Get the user who made the change.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reference model (polymorphic-like behavior).
     */
    public function getReference()
    {
        if (!$this->reference_type || !$this->reference_id) {
            return null;
        }

        $modelClass = $this->reference_type;
        
        if (!class_exists($modelClass)) {
            return null;
        }

        return $modelClass::find($this->reference_id);
    }

    /**
     * Create an audit entry for a product request being received.
     */
    public static function createForProductRequestReceived(
        ProductRequest $productRequest,
        int $previousStock,
        int $newStock,
        ?int $userId = null
    ): self {
        return self::create([
            'spare_part_id' => $productRequest->spare_part_id,
            'change_type' => self::CHANGE_TYPE_PRODUCT_REQUEST_RECEIVED,
            'quantity_change' => $productRequest->quantity_requested,
            'previous_stock' => $previousStock,
            'new_stock' => $newStock,
            'reference_type' => ProductRequest::class,
            'reference_id' => $productRequest->id,
            'user_id' => $userId,
            'notes' => "Solicitud de producto #{$productRequest->id} recibida",
        ]);
    }

    /**
     * Create an audit entry for a product request being reversed.
     */
    public static function createForProductRequestReversed(
        ProductRequest $productRequest,
        int $previousStock,
        int $newStock,
        ?int $userId = null
    ): self {
        return self::create([
            'spare_part_id' => $productRequest->spare_part_id,
            'change_type' => self::CHANGE_TYPE_PRODUCT_REQUEST_REVERSED,
            'quantity_change' => -$productRequest->quantity_requested,
            'previous_stock' => $previousStock,
            'new_stock' => $newStock,
            'reference_type' => ProductRequest::class,
            'reference_id' => $productRequest->id,
            'user_id' => $userId,
            'notes' => "Solicitud de producto #{$productRequest->id} revertida (cambio de estado desde 'recibida')",
        ]);
    }

    /**
     * Get a human-readable description of the change.
     */
    public function getChangeDescription(): string
    {
        $changeTypeDescriptions = [
            self::CHANGE_TYPE_PRODUCT_REQUEST_RECEIVED => 'Solicitud de producto recibida',
            self::CHANGE_TYPE_PRODUCT_REQUEST_REVERSED => 'Solicitud de producto revertida',
            self::CHANGE_TYPE_MAINTENANCE_USAGE => 'Uso en mantenimiento',
            self::CHANGE_TYPE_MANUAL_ADJUSTMENT => 'Ajuste manual',
        ];

        return $changeTypeDescriptions[$this->change_type] ?? $this->change_type;
    }

    /**
     * Get the change direction (increase or decrease).
     */
    public function getChangeDirection(): string
    {
        return $this->quantity_change > 0 ? 'increase' : 'decrease';
    }

    /**
     * Scope to filter by spare part.
     */
    public function scopeForSparePart($query, int $sparePartId)
    {
        return $query->where('spare_part_id', $sparePartId);
    }

    /**
     * Scope to filter by change type.
     */
    public function scopeByChangeType($query, string $changeType)
    {
        return $query->where('change_type', $changeType);
    }

    /**
     * Scope to filter by reference.
     */
    public function scopeByReference($query, string $referenceType, int $referenceId)
    {
        return $query->where('reference_type', $referenceType)
                     ->where('reference_id', $referenceId);
    }

    /**
     * Scope to get recent audits.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
