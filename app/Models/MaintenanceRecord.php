<?php

namespace App\Models;

use App\Traits\HasAutoAssignment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MaintenanceRecord extends Model
{
    use HasAutoAssignment;
    protected $fillable = [
        'vehicle_id',
        'vehicle_type',
        'maintenance_type',
        'date',
        'description',
        'mechanic_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    protected $appends = ['calculated_cost'];

    /**
     * Get the vehicle that this maintenance record belongs to.
     * This is a polymorphic relationship that can be either Vehicle or Trailer.
     */
    public function vehicle(): MorphTo
    {
        return $this->morphTo('vehicle', 'vehicle_type', 'vehicle_id');
    }

    /**
     * Get the mechanic (user) who performed this maintenance.
     */
    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mechanic_id');
    }

    /**
     * Get the spare parts used in this maintenance record.
     */
    public function spareParts(): BelongsToMany
    {
        return $this->belongsToMany(SparePart::class, 'maintenance_spares')
            ->withPivot('quantity_used', 'cost')
            ->withTimestamps();
    }

    /**
     * Get the product usage records for this maintenance record.
     */
    public function productUsages(): HasMany
    {
        return $this->hasMany(ProductUsage::class);
    }

    /**
     * Get all attachments for this maintenance record.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Scope to filter by vehicle type.
     */
    public function scopeForVehicleType($query, string $type)
    {
        return $query->where('vehicle_type', $type);
    }

    /**
     * Scope to filter by maintenance type.
     */
    public function scopeByMaintenanceType($query, string $type)
    {
        return $query->where('maintenance_type', $type);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Get the total cost including spare parts.
     */
    public function getTotalCostAttribute(): float
    {
        $sparePartsCost = $this->spareParts->sum('pivot.cost');
        return $this->cost + $sparePartsCost;
    }

    /**
     * Get total cost including product usage costs.
     */
    public function getTotalCostWithUsageAttribute(): float
    {
        $baseCost = $this->getTotalCostAttribute();
        $usageCost = $this->getProductUsageCost();
        
        return $baseCost + $usageCost;
    }

    /**
     * Calculate cost of products used through ProductUsage records.
     */
    public function getProductUsageCost(): float
    {
        return $this->productUsages()
            ->with('sparePart')
            ->get()
            ->sum(function ($usage) {
                return $usage->quantity_used * ($usage->sparePart->unit_cost ?? 0);
            });
    }

    /**
     * Get the automatically calculated cost based on product usage.
     * This is the main cost accessor that replaces the old 'cost' field.
     */
    public function getCalculatedCostAttribute(): float
    {
        return $this->getProductUsageCost();
    }

    /**
     * Alias for calculated_cost for backward compatibility.
     */
    public function getCostAttribute(): float
    {
        return $this->getCalculatedCostAttribute();
    }

    /**
     * Get summary of parts used in this maintenance session.
     */
    public function getPartsUsedSummary(): array
    {
        $pivotParts = $this->spareParts->map(function ($part) {
            return [
                'part_number' => $part->part_number,
                'name' => $part->name,
                'brand' => $part->brand,
                'quantity_used' => $part->pivot->quantity_used,
                'unit_cost' => $part->unit_cost,
                'total_cost' => $part->pivot->cost,
                'source' => 'maintenance_spares',
                'date_used' => $part->pivot->created_at,
            ];
        });

        $usageParts = $this->productUsages->map(function ($usage) {
            return [
                'part_number' => $usage->sparePart->part_number,
                'name' => $usage->sparePart->name,
                'brand' => $usage->sparePart->brand,
                'quantity_used' => $usage->quantity_used,
                'unit_cost' => $usage->sparePart->unit_cost,
                'total_cost' => $usage->quantity_used * $usage->sparePart->unit_cost,
                'source' => 'product_usage',
                'date_used' => $usage->date_used,
                'notes' => $usage->notes,
                'used_by' => $usage->usedBy->name ?? 'Sistema',
            ];
        });

        return $pivotParts->concat($usageParts)->sortBy('date_used')->values()->all();
    }

    /**
     * Get total quantity of a specific part used in this maintenance.
     */
    public function getTotalPartQuantityUsed(int $sparePartId): float
    {
        $pivotQuantity = $this->spareParts()
            ->where('spare_part_id', $sparePartId)
            ->sum('maintenance_spares.quantity_used');

        $usageQuantity = $this->productUsages()
            ->where('spare_part_id', $sparePartId)
            ->sum('quantity_used');

        return $pivotQuantity + $usageQuantity;
    }

    /**
     * Validate that maintenance record can accept product usage.
     */
    public function canAcceptProductUsage(): bool
    {
        // Check if maintenance record is not too old (configurable)
        $maxDaysOld = config('maintenance.max_days_for_usage', 30);
        $isNotTooOld = Carbon::parse($this->date)->diffInDays(now()) <= $maxDaysOld;
        
        // Check if maintenance record is not in a final state (if you have status)
        // For now, we'll assume all records can accept usage
        $canModify = true;
        
        return $isNotTooOld && $canModify;
    }

    /**
     * Validate product usage before adding to this maintenance record.
     */
    public function validateProductUsage(int $sparePartId, float $quantity): array
    {
        $errors = [];
        
        if (!$this->canAcceptProductUsage()) {
            $errors[] = 'Este registro de mantenimiento no puede aceptar más uso de productos';
        }
        
        $sparePart = SparePart::find($sparePartId);
        if (!$sparePart) {
            $errors[] = 'El repuesto especificado no existe';
        } elseif ($sparePart->stock_quantity < $quantity) {
            $errors[] = "Stock insuficiente para {$sparePart->name}. Disponible: {$sparePart->stock_quantity}";
        }
        
        if ($quantity <= 0) {
            $errors[] = 'La cantidad debe ser mayor a 0';
        }
        
        return $errors;
    }

    /**
     * Add product usage to this maintenance record with validation.
     */
    public function addProductUsage(int $sparePartId, float $quantity, ?string $notes = null, ?int $userId = null): ProductUsage
    {
        $errors = $this->validateProductUsage($sparePartId, $quantity);
        
        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }
        
        return $this->productUsages()->create([
            'spare_part_id' => $sparePartId,
            'quantity_used' => $quantity,
            'date_used' => now()->toDateString(),
            'notes' => $notes,
            'used_by' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Get maintenance statistics including product usage.
     */
    public function getMaintenanceStatistics(): array
    {
        $partsUsed = $this->getPartsUsedSummary();
        $totalParts = count($partsUsed);
        $totalCost = $this->getTotalCostWithUsageAttribute();
        $laborCost = $this->cost;
        $partsCost = $totalCost - $laborCost;
        
        return [
            'total_cost' => $totalCost,
            'labor_cost' => $laborCost,
            'parts_cost' => $partsCost,
            'total_parts_used' => $totalParts,
            'unique_parts_count' => collect($partsUsed)->unique('part_number')->count(),
            'parts_breakdown' => $partsUsed,
            'cost_breakdown' => [
                'labor_percentage' => $totalCost > 0 ? ($laborCost / $totalCost) * 100 : 0,
                'parts_percentage' => $totalCost > 0 ? ($partsCost / $totalCost) * 100 : 0,
            ],
        ];
    }

    /**
     * Scope to include maintenance records with product usage.
     */
    public function scopeWithProductUsage($query)
    {
        return $query->with(['productUsages.sparePart', 'productUsages.usedBy']);
    }

    /**
     * Scope to filter maintenance records by parts used.
     */
    public function scopeUsingPart($query, int $sparePartId)
    {
        return $query->where(function ($q) use ($sparePartId) {
            $q->whereHas('spareParts', function ($sq) use ($sparePartId) {
                $sq->where('spare_part_id', $sparePartId);
            })->orWhereHas('productUsages', function ($uq) use ($sparePartId) {
                $uq->where('spare_part_id', $sparePartId);
            });
        });
    }

    /**
     * Scope to filter by cost range including product usage.
     */
    public function scopeByCostRange($query, float $minCost, float $maxCost)
    {
        return $query->whereRaw('
            (cost + COALESCE((
                SELECT SUM(pu.quantity_used * sp.unit_cost)
                FROM product_usages pu
                JOIN spare_parts sp ON pu.spare_part_id = sp.id
                WHERE pu.maintenance_record_id = maintenance_records.id
            ), 0) + COALESCE((
                SELECT SUM(ms.cost)
                FROM maintenance_spares ms
                WHERE ms.maintenance_record_id = maintenance_records.id
            ), 0)) BETWEEN ? AND ?
        ', [$minCost, $maxCost]);
    }
}
