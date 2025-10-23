<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ProductRequest extends Model
{
    protected $fillable = [
        'spare_part_id',
        'quantity_requested',
        'priority',
        'justification',
        'status',
        'requested_by',
        'approved_by',
        'requested_at',
        'approved_at',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:2',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * Priority levels with display names.
     */
    public const PRIORITIES = [
        'low' => 'Baja',
        'medium' => 'Media',
        'high' => 'Alta',
        'urgent' => 'Urgente',
    ];

    /**
     * Status levels with display names.
     */
    public const STATUSES = [
        'pending' => 'Pendiente',
        'approved' => 'Aprobada',
        'ordered' => 'Ordenada',
        'received' => 'Recibida',
    ];

    /**
     * Get the spare part being requested.
     */
    public function sparePart(): BelongsTo
    {
        return $this->belongsTo(SparePart::class);
    }

    /**
     * Get the user who made the request.
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user who approved the request.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Validation rules for product requests.
     */
    public static function rules(): array
    {
        return [
            'spare_part_id' => 'required|exists:spare_parts,id',
            'quantity_requested' => 'required|numeric|min:0.01',
            'priority' => 'required|in:low,medium,high,urgent',
            'justification' => 'required|string|min:10|max:1000',
            'status' => 'in:pending,approved,ordered,received',
            'requested_by' => 'required|exists:users,id',
            'approved_by' => 'nullable|exists:users,id',
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
            'quantity_requested.required' => 'Debe especificar la cantidad solicitada.',
            'quantity_requested.numeric' => 'La cantidad debe ser un número.',
            'quantity_requested.min' => 'La cantidad debe ser mayor a 0.',
            'priority.required' => 'Debe seleccionar una prioridad.',
            'priority.in' => 'La prioridad seleccionada no es válida.',
            'justification.required' => 'Debe proporcionar una justificación.',
            'justification.min' => 'La justificación debe tener al menos 10 caracteres.',
            'justification.max' => 'La justificación no puede exceder 1000 caracteres.',
            'status.in' => 'El estado seleccionado no es válido.',
            'requested_by.required' => 'Debe especificar quién hizo la solicitud.',
            'requested_by.exists' => 'El usuario especificado no existe.',
            'approved_by.exists' => 'El usuario aprobador especificado no existe.',
        ];
    }

    /**
     * Approve the request.
     */
    public function approve(int $approvedBy): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $this->status = 'approved';
        $this->approved_by = $approvedBy;
        $this->approved_at = now();

        return $this->save();
    }

    /**
     * Mark the request as ordered.
     */
    public function markAsOrdered(): bool
    {
        if ($this->status !== 'approved') {
            return false;
        }

        $this->status = 'ordered';
        return $this->save();
    }

    /**
     * Mark the request as received.
     */
    public function markAsReceived(): bool
    {
        if ($this->status !== 'ordered') {
            return false;
        }

        $this->status = 'received';
        return $this->save();
    }

    /**
     * Reject the request (return to pending).
     */
    public function reject(): bool
    {
        if ($this->status === 'received') {
            return false;
        }

        $this->status = 'pending';
        $this->approved_by = null;
        $this->approved_at = null;

        return $this->save();
    }

    /**
     * Check if the request can be approved.
     */
    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the request can be ordered.
     */
    public function canBeOrdered(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the request can be marked as received.
     */
    public function canBeReceived(): bool
    {
        return $this->status === 'ordered';
    }

    /**
     * Check if the request can be rejected.
     */
    public function canBeRejected(): bool
    {
        return in_array($this->status, ['approved', 'ordered']);
    }

    /**
     * Get the priority display name.
     */
    public function getPriorityDisplayAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }

    /**
     * Get the status display name.
     */
    public function getStatusDisplayAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by priority.
     */
    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to get pending requests.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get approved requests.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get ordered requests.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->where('status', 'ordered');
    }

    /**
     * Scope to get received requests.
     */
    public function scopeReceived(Builder $query): Builder
    {
        return $query->where('status', 'received');
    }

    /**
     * Scope to get urgent requests.
     */
    public function scopeUrgent(Builder $query): Builder
    {
        return $query->where('priority', 'urgent');
    }

    /**
     * Scope to get high priority requests.
     */
    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    /**
     * Scope to filter by requester.
     */
    public function scopeByRequester(Builder $query, int $userId): Builder
    {
        return $query->where('requested_by', $userId);
    }

    /**
     * Scope to filter by approver.
     */
    public function scopeByApprover(Builder $query, int $userId): Builder
    {
        return $query->where('approved_by', $userId);
    }

    /**
     * Scope to order by priority (urgent first).
     */
    public function scopeOrderByPriority(Builder $query): Builder
    {
        return $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')");
    }
}
