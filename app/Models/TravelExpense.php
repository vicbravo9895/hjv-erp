<?php

namespace App\Models;

use App\Traits\HasAttachments;
use App\Traits\HasAutoAssignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;

class TravelExpense extends Model
{
    use HasAttachments, HasAutoAssignment;
    protected $fillable = [
        'trip_id',
        'operator_id',
        'expense_type',
        'amount',
        'date',
        'location',
        'description',
        'fuel_liters',
        'fuel_price_per_liter',
        'odometer_reading',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'fuel_liters' => 'decimal:2',
        'fuel_price_per_liter' => 'decimal:3',
        'odometer_reading' => 'integer',
    ];

    /**
     * Expense types with display names.
     */
    public const EXPENSE_TYPES = [
        'fuel' => 'Combustible',
        'tolls' => 'Peajes',
        'food' => 'Alimentación',
        'accommodation' => 'Hospedaje',
        'other' => 'Otros',
    ];

    /**
     * Status levels with display names.
     */
    public const STATUSES = [
        'pending' => 'Pendiente',
        'approved' => 'Aprobado',
        'reimbursed' => 'Reembolsado',
    ];

    /**
     * Get the trip this expense belongs to.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the operator who incurred this expense.
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }



    /**
     * Validation rules for travel expenses.
     */
    public static function rules(): array
    {
        return [
            'trip_id' => 'required|exists:trips,id',
            'operator_id' => 'required|exists:users,id',
            'expense_type' => 'required|in:fuel,tolls,food,accommodation,other',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date|before_or_equal:today',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'fuel_liters' => 'nullable|numeric|min:0.01|required_if:expense_type,fuel',
            'fuel_price_per_liter' => 'nullable|numeric|min:0.001|required_if:expense_type,fuel',
            'odometer_reading' => 'nullable|integer|min:0|required_if:expense_type,fuel',
            'status' => 'in:pending,approved,reimbursed',
        ];
    }

    /**
     * Custom validation messages.
     */
    public static function messages(): array
    {
        return [
            'trip_id.required' => 'Debe seleccionar un viaje.',
            'trip_id.exists' => 'El viaje seleccionado no existe.',
            'operator_id.required' => 'Debe especificar el operador.',
            'operator_id.exists' => 'El operador especificado no existe.',
            'expense_type.required' => 'Debe seleccionar un tipo de gasto.',
            'expense_type.in' => 'El tipo de gasto seleccionado no es válido.',
            'amount.required' => 'Debe especificar el monto del gasto.',
            'amount.numeric' => 'El monto debe ser un número.',
            'amount.min' => 'El monto debe ser mayor a 0.',
            'date.required' => 'Debe especificar la fecha del gasto.',
            'date.date' => 'La fecha debe ser válida.',
            'date.before_or_equal' => 'La fecha no puede ser futura.',
            'location.max' => 'La ubicación no puede exceder 255 caracteres.',
            'description.max' => 'La descripción no puede exceder 1000 caracteres.',
            'fuel_liters.required_if' => 'Los litros de combustible son requeridos para gastos de combustible.',
            'fuel_liters.numeric' => 'Los litros deben ser un número.',
            'fuel_liters.min' => 'Los litros deben ser mayor a 0.',
            'fuel_price_per_liter.required_if' => 'El precio por litro es requerido para gastos de combustible.',
            'fuel_price_per_liter.numeric' => 'El precio por litro debe ser un número.',
            'fuel_price_per_liter.min' => 'El precio por litro debe ser mayor a 0.',
            'odometer_reading.required_if' => 'La lectura del odómetro es requerida para gastos de combustible.',
            'odometer_reading.integer' => 'La lectura del odómetro debe ser un número entero.',
            'odometer_reading.min' => 'La lectura del odómetro no puede ser negativa.',
            'status.in' => 'El estado seleccionado no es válido.',
        ];
    }

    /**
     * Check if this is a fuel expense.
     */
    public function isFuelExpense(): bool
    {
        return $this->expense_type === 'fuel';
    }

    /**
     * Get the expense type display name.
     */
    public function getExpenseTypeDisplayAttribute(): string
    {
        return self::EXPENSE_TYPES[$this->expense_type] ?? $this->expense_type;
    }

    /**
     * Get the status display name.
     */
    public function getStatusDisplayAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Calculate fuel cost based on liters and price per liter.
     */
    public function calculateFuelCost(): ?float
    {
        if (!$this->isFuelExpense() || !$this->fuel_liters || !$this->fuel_price_per_liter) {
            return null;
        }

        return (float) ($this->fuel_liters * $this->fuel_price_per_liter);
    }

    /**
     * Validate fuel expense data consistency.
     */
    public function validateFuelData(): bool
    {
        if (!$this->isFuelExpense()) {
            return true;
        }

        $calculatedAmount = $this->calculateFuelCost();
        if ($calculatedAmount === null) {
            return false;
        }

        // Allow small rounding differences (within 0.01)
        return abs($this->amount - $calculatedAmount) <= 0.01;
    }

    /**
     * Automatically associate with operator's active trip.
     */
    public static function getActiveTripsForOperator(int $operatorId): \Illuminate\Database\Eloquent\Collection
    {
        return Trip::where('operator_id', $operatorId)
            ->active()
            ->orderBy('start_date', 'desc')
            ->get();
    }

    /**
     * Boot the model to handle automatic trip association.
     */
    protected static function booted(): void
    {
        static::creating(function (TravelExpense $expense) {
            // If no trip is specified, try to associate with operator's active trip
            if (!$expense->trip_id && $expense->operator_id) {
                $activeTrip = Trip::where('operator_id', $expense->operator_id)
                    ->active()
                    ->orderBy('start_date', 'desc')
                    ->first();
                
                if ($activeTrip) {
                    $expense->trip_id = $activeTrip->id;
                }
            }
        });

        static::saving(function (TravelExpense $expense) {
            // Validate fuel data consistency
            if ($expense->isFuelExpense() && !$expense->validateFuelData()) {
                throw new \InvalidArgumentException(
                    'Los datos de combustible no son consistentes. Verifique litros, precio por litro y monto total.'
                );
            }
        });
    }

    /**
     * Scope to filter by expense type.
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('expense_type', $type);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending expenses.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get approved expenses.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get reimbursed expenses.
     */
    public function scopeReimbursed(Builder $query): Builder
    {
        return $query->where('status', 'reimbursed');
    }

    /**
     * Scope to get fuel expenses.
     */
    public function scopeFuel(Builder $query): Builder
    {
        return $query->where('expense_type', 'fuel');
    }

    /**
     * Scope to filter by operator.
     */
    public function scopeByOperator(Builder $query, int $operatorId): Builder
    {
        return $query->where('operator_id', $operatorId);
    }

    /**
     * Scope to filter by trip.
     */
    public function scopeByTrip(Builder $query, int $tripId): Builder
    {
        return $query->where('trip_id', $tripId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }
}
