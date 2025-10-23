<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SparePart extends Model
{
    protected $fillable = [
        'part_number',
        'name',
        'brand',
        'stock_quantity',
        'unit_cost',
        'location',
    ];

    protected $casts = [
        'stock_quantity' => 'integer',
        'unit_cost' => 'decimal:2',
    ];

    /**
     * Get the maintenance records that used this spare part.
     */
    public function maintenanceRecords(): BelongsToMany
    {
        return $this->belongsToMany(MaintenanceRecord::class, 'maintenance_spares')
            ->withPivot('quantity_used', 'cost')
            ->withTimestamps();
    }

    /**
     * Get the product usage records for this spare part.
     */
    public function productUsages(): HasMany
    {
        return $this->hasMany(ProductUsage::class);
    }

    /**
     * Get the product requests for this spare part.
     */
    public function productRequests(): HasMany
    {
        return $this->hasMany(ProductRequest::class);
    }

    /**
     * Get the inventory audit trail for this spare part.
     */
    public function inventoryAudits(): HasMany
    {
        return $this->hasMany(\App\Models\InventoryAudit::class);
    }

    /**
     * Scope to filter by brand.
     */
    public function scopeByBrand($query, string $brand)
    {
        return $query->where('brand', $brand);
    }

    /**
     * Scope to filter parts with low stock.
     */
    public function scopeLowStock($query, int $threshold = 10)
    {
        return $query->where('stock_quantity', '<=', $threshold);
    }

    /**
     * Scope to filter parts in stock.
     */
    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    /**
     * Check if the part is in stock.
     */
    public function isInStock(): bool
    {
        return $this->stock_quantity > 0;
    }

    /**
     * Check if the part has low stock.
     */
    public function hasLowStock(int $threshold = 10): bool
    {
        return $this->stock_quantity <= $threshold;
    }

    /**
     * Reduce stock quantity.
     */
    public function reduceStock(int $quantity): bool
    {
        if ($this->stock_quantity >= $quantity) {
            $this->stock_quantity -= $quantity;
            return $this->save();
        }
        return false;
    }

    /**
     * Increase stock quantity.
     */
    public function increaseStock(int $quantity): bool
    {
        $this->stock_quantity += $quantity;
        return $this->save();
    }

    /**
     * Get the display name for the spare part.
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->part_number} - {$this->name} ({$this->brand})";
    }

    /**
     * Get inventory history for this spare part.
     */
    public function getInventoryHistory()
    {
        return $this->productUsages()
            ->with(['usedBy', 'maintenanceRecord'])
            ->orderBy('date_used', 'desc')
            ->get()
            ->map(function ($usage) {
                return [
                    'date' => $usage->date_used,
                    'type' => 'usage',
                    'quantity' => -$usage->quantity_used,
                    'balance_after' => null, // Will be calculated
                    'reference' => "Mantenimiento #{$usage->maintenance_record_id}",
                    'user' => $usage->usedBy->name ?? 'Sistema',
                    'notes' => $usage->notes,
                ];
            });
    }

    /**
     * Get total quantity used in a date range.
     */
    public function getTotalUsedInPeriod($startDate, $endDate): float
    {
        return $this->productUsages()
            ->whereBetween('date_used', [$startDate, $endDate])
            ->sum('quantity_used');
    }

    /**
     * Get average monthly usage.
     */
    public function getAverageMonthlyUsage(int $months = 6): float
    {
        $startDate = now()->subMonths($months);
        $endDate = now();
        
        $totalUsed = $this->getTotalUsedInPeriod($startDate, $endDate);
        
        return $months > 0 ? $totalUsed / $months : 0;
    }

    /**
     * Calculate estimated days until stock out based on usage history.
     */
    public function getEstimatedDaysUntilStockOut(): ?int
    {
        $averageMonthlyUsage = $this->getAverageMonthlyUsage();
        
        if ($averageMonthlyUsage <= 0) {
            return null; // No usage history or no usage
        }
        
        $averageDailyUsage = $averageMonthlyUsage / 30;
        
        return (int) ceil($this->stock_quantity / $averageDailyUsage);
    }

    /**
     * Check if part needs reordering based on usage patterns.
     */
    public function needsReordering(int $leadTimeDays = 30, int $safetyStockDays = 15): bool
    {
        $estimatedDays = $this->getEstimatedDaysUntilStockOut();
        
        if ($estimatedDays === null) {
            // No usage history, use basic low stock check
            return $this->hasLowStock();
        }
        
        $reorderPoint = $leadTimeDays + $safetyStockDays;
        
        return $estimatedDays <= $reorderPoint;
    }

    /**
     * Get low stock alert level based on usage patterns.
     */
    public function getLowStockAlertLevel(): string
    {
        $estimatedDays = $this->getEstimatedDaysUntilStockOut();
        
        if ($estimatedDays === null) {
            return $this->hasLowStock() ? 'warning' : 'normal';
        }
        
        if ($estimatedDays <= 7) {
            return 'critical';
        } elseif ($estimatedDays <= 15) {
            return 'warning';
        } elseif ($estimatedDays <= 30) {
            return 'attention';
        }
        
        return 'normal';
    }

    /**
     * Get parts that need immediate attention.
     */
    public static function getPartsNeedingAttention()
    {
        return static::all()->filter(function ($part) {
            return in_array($part->getLowStockAlertLevel(), ['critical', 'warning']);
        });
    }

    /**
     * Get stock status summary.
     */
    public function getStockStatusSummary(): array
    {
        $alertLevel = $this->getLowStockAlertLevel();
        $estimatedDays = $this->getEstimatedDaysUntilStockOut();
        $averageUsage = $this->getAverageMonthlyUsage();
        
        return [
            'current_stock' => $this->stock_quantity,
            'alert_level' => $alertLevel,
            'estimated_days_until_stockout' => $estimatedDays,
            'average_monthly_usage' => $averageUsage,
            'needs_reordering' => $this->needsReordering(),
            'total_value' => $this->stock_quantity * $this->unit_cost,
        ];
    }

    /**
     * Validate stock operation before execution.
     */
    public function validateStockOperation(int $quantity, string $operation = 'reduce'): array
    {
        $errors = [];
        
        if ($quantity <= 0) {
            $errors[] = 'La cantidad debe ser mayor a 0';
        }
        
        if ($operation === 'reduce' && $this->stock_quantity < $quantity) {
            $errors[] = "Stock insuficiente. Disponible: {$this->stock_quantity}, Solicitado: {$quantity}";
        }
        
        return $errors;
    }

    /**
     * Enhanced reduce stock with validation and logging.
     */
    public function reduceStockWithValidation(int $quantity, ?string $reason = null): bool
    {
        $errors = $this->validateStockOperation($quantity, 'reduce');
        
        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }
        
        $oldStock = $this->stock_quantity;
        $this->stock_quantity -= $quantity;
        $result = $this->save();
        
        if ($result && $reason) {
            \Log::info("Stock reduced for part {$this->part_number}: {$oldStock} -> {$this->stock_quantity}. Reason: {$reason}");
        }
        
        return $result;
    }

    /**
     * Enhanced increase stock with logging.
     */
    public function increaseStockWithLogging(int $quantity, ?string $reason = null): bool
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('La cantidad debe ser mayor a 0');
        }
        
        $oldStock = $this->stock_quantity;
        $this->stock_quantity += $quantity;
        $result = $this->save();
        
        if ($result && $reason) {
            \Log::info("Stock increased for part {$this->part_number}: {$oldStock} -> {$this->stock_quantity}. Reason: {$reason}");
        }
        
        return $result;
    }
}
