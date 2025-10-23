<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceSpare extends Model
{
    protected $fillable = [
        'maintenance_record_id',
        'spare_part_id',
        'quantity_used',
        'cost',
    ];

    protected $casts = [
        'quantity_used' => 'integer',
        'cost' => 'decimal:2',
    ];

    /**
     * Get the maintenance record that this spare part usage belongs to.
     */
    public function maintenanceRecord(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRecord::class);
    }

    /**
     * Get the spare part that was used.
     */
    public function sparePart(): BelongsTo
    {
        return $this->belongsTo(SparePart::class);
    }

    /**
     * Calculate the total cost for this spare part usage.
     */
    public function getTotalCostAttribute(): float
    {
        return $this->quantity_used * $this->cost;
    }
}
