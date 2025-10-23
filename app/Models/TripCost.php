<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'cost_type',
        'amount',
        'description',
        'receipt_url',
        'location',
        'quantity',
        'unit_price',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:3',
    ];

    /**
     * Cost type constants
     */
    const TYPE_DIESEL = 'diesel';
    const TYPE_TOLLS = 'tolls';
    const TYPE_MANEUVERS = 'maneuvers';
    const TYPE_OTHER = 'other';

    /**
     * Get all available cost types
     */
    public static function getCostTypes(): array
    {
        return [
            self::TYPE_DIESEL => 'Diésel',
            self::TYPE_TOLLS => 'Peajes',
            self::TYPE_MANEUVERS => 'Maniobras',
            self::TYPE_OTHER => 'Otros',
        ];
    }

    /**
     * Get the trip that this cost belongs to.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the formatted cost type name.
     */
    public function getCostTypeNameAttribute(): string
    {
        return self::getCostTypes()[$this->cost_type] ?? $this->cost_type;
    }

    /**
     * Scope to filter by cost type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('cost_type', $type);
    }

    /**
     * Scope to get diesel costs.
     */
    public function scopeDiesel($query)
    {
        return $query->where('cost_type', self::TYPE_DIESEL);
    }

    /**
     * Scope to get toll costs.
     */
    public function scopeTolls($query)
    {
        return $query->where('cost_type', self::TYPE_TOLLS);
    }

    /**
     * Scope to get maneuver costs.
     */
    public function scopeManeuvers($query)
    {
        return $query->where('cost_type', self::TYPE_MANEUVERS);
    }

    /**
     * Check if this is a diesel cost.
     */
    public function isDiesel(): bool
    {
        return $this->cost_type === self::TYPE_DIESEL;
    }

    /**
     * Check if this is a toll cost.
     */
    public function isToll(): bool
    {
        return $this->cost_type === self::TYPE_TOLLS;
    }

    /**
     * Check if this is a maneuver cost.
     */
    public function isManeuver(): bool
    {
        return $this->cost_type === self::TYPE_MANEUVERS;
    }

    /**
     * Get the display name for the cost.
     */
    public function getDisplayNameAttribute(): string
    {
        $name = $this->cost_type_name;
        if ($this->location) {
            $name .= " - {$this->location}";
        }
        return $name;
    }

    /**
     * Calculate amount from quantity and unit price if not set.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($tripCost) {
            // Auto-calculate amount if quantity and unit_price are provided but amount is not
            if ($tripCost->quantity && $tripCost->unit_price && !$tripCost->amount) {
                $tripCost->amount = $tripCost->quantity * $tripCost->unit_price;
            }
        });
    }
}
