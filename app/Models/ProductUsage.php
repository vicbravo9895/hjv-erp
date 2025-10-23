<?php

namespace App\Models;

use App\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class ProductUsage extends Model
{
    use HasAttachments;
    protected $fillable = [
        'spare_part_id',
        'maintenance_record_id',
        'quantity_used',
        'date_used',
        'notes',
        'used_by',
    ];

    protected $casts = [
        'quantity_used' => 'decimal:2',
        'date_used' => 'date',
    ];

    /**
     * Get the spare part that was used.
     */
    public function sparePart(): BelongsTo
    {
        return $this->belongsTo(SparePart::class);
    }

    /**
     * Get the maintenance record this usage belongs to.
     */
    public function maintenanceRecord(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRecord::class);
    }

    /**
     * Get the user who recorded this usage.
     */
    public function usedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by');
    }



    /**
     * Validation rules for product usage.
     */
    public static function rules(): array
    {
        return [
            'spare_part_id' => 'required|exists:spare_parts,id',
            'maintenance_record_id' => 'required|exists:maintenance_records,id',
            'quantity_used' => 'required|numeric|min:0.01',
            'date_used' => 'required|date|before_or_equal:today',
            'notes' => 'nullable|string|max:1000',
            'used_by' => 'required|exists:users,id',
        ];
    }

    /**
     * Custom validation messages.
     */
    public static function messages(): array
    {
        return [
            'spare_part_id.required' => 'Debe seleccionar un repuesto.',
            'spare_part_id.exists' => 'El repuesto seleccionado no existe.',
            'maintenance_record_id.required' => 'Debe seleccionar un registro de mantenimiento.',
            'maintenance_record_id.exists' => 'El registro de mantenimiento seleccionado no existe.',
            'quantity_used.required' => 'Debe especificar la cantidad utilizada.',
            'quantity_used.numeric' => 'La cantidad debe ser un número.',
            'quantity_used.min' => 'La cantidad debe ser mayor a 0.',
            'date_used.required' => 'Debe especificar la fecha de uso.',
            'date_used.date' => 'La fecha debe ser válida.',
            'date_used.before_or_equal' => 'La fecha no puede ser futura.',
            'notes.max' => 'Las notas no pueden exceder 1000 caracteres.',
            'used_by.required' => 'Debe especificar quién registró el uso.',
            'used_by.exists' => 'El usuario especificado no existe.',
        ];
    }

    /**
     * Validate that there's enough stock before creating the usage.
     */
    public function validateStock(): void
    {
        if (!$this->sparePart) {
            throw ValidationException::withMessages([
                'spare_part_id' => 'No se pudo encontrar el repuesto especificado.'
            ]);
        }

        if ($this->sparePart->stock_quantity < $this->quantity_used) {
            throw ValidationException::withMessages([
                'quantity_used' => "Stock insuficiente. Disponible: {$this->sparePart->stock_quantity}, Solicitado: {$this->quantity_used}"
            ]);
        }
    }

    /**
     * Boot the model to handle automatic inventory deduction.
     */
    protected static function booted(): void
    {
        static::creating(function (ProductUsage $productUsage) {
            $productUsage->validateStock();
        });

        static::created(function (ProductUsage $productUsage) {
            // Automatically reduce stock when product usage is created
            $productUsage->sparePart->reduceStock((int) $productUsage->quantity_used);
        });

        static::updating(function (ProductUsage $productUsage) {
            if ($productUsage->isDirty('quantity_used') || $productUsage->isDirty('spare_part_id')) {
                // If quantity or spare part changed, we need to revert old stock and validate new
                $original = $productUsage->getOriginal();
                
                // Restore original stock
                if ($original['spare_part_id']) {
                    $originalSparePart = SparePart::find($original['spare_part_id']);
                    if ($originalSparePart) {
                        $originalSparePart->increaseStock((int) $original['quantity_used']);
                    }
                }
                
                // Validate new stock
                $productUsage->validateStock();
            }
        });

        static::updated(function (ProductUsage $productUsage) {
            if ($productUsage->wasChanged('quantity_used') || $productUsage->wasChanged('spare_part_id')) {
                // Deduct new stock
                $productUsage->sparePart->reduceStock((int) $productUsage->quantity_used);
            }
        });

        static::deleting(function (ProductUsage $productUsage) {
            // Restore stock when usage is deleted
            $productUsage->sparePart->increaseStock((int) $productUsage->quantity_used);
        });
    }

    /**
     * Scope to filter by spare part.
     */
    public function scopeBySparePart($query, int $sparePartId)
    {
        return $query->where('spare_part_id', $sparePartId);
    }

    /**
     * Scope to filter by maintenance record.
     */
    public function scopeByMaintenanceRecord($query, int $maintenanceRecordId)
    {
        return $query->where('maintenance_record_id', $maintenanceRecordId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date_used', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('used_by', $userId);
    }
}
