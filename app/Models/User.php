<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'license_number',
        'phone',
        'hire_date',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'hire_date' => 'date',
        ];
    }

    /**
     * Check if user has accounting access
     */
    public function hasAccountingAccess(): bool
    {
        return in_array($this->role, ['super_admin', 'administrador', 'contador']);
    }

    /**
     * Check if user has admin access
     */
    public function hasAdminAccess(): bool
    {
        return in_array($this->role, ['super_admin', 'administrador', 'supervisor']);
    }

    /**
     * Check if user has workshop access
     */
    public function hasWorkshopAccess(): bool
    {
        return in_array($this->role, ['super_admin', 'administrador', 'supervisor']);
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if user is administrator
     */
    public function isAdministrator(): bool
    {
        return $this->role === 'administrador';
    }

    /**
     * Check if user is supervisor
     */
    public function isSupervisor(): bool
    {
        return $this->role === 'supervisor';
    }

    /**
     * Check if user is accountant
     */
    public function isAccountant(): bool
    {
        return $this->role === 'contador';
    }

    /**
     * Check if user is operator
     */
    public function isOperator(): bool
    {
        return $this->role === 'operador';
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has any of the specified roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Get product usages recorded by this user.
     */
    public function productUsages(): HasMany
    {
        return $this->hasMany(ProductUsage::class, 'used_by');
    }

    /**
     * Get product requests made by this user.
     */
    public function productRequests(): HasMany
    {
        return $this->hasMany(ProductRequest::class, 'requested_by');
    }

    /**
     * Get product requests approved by this user.
     */
    public function approvedProductRequests(): HasMany
    {
        return $this->hasMany(ProductRequest::class, 'approved_by');
    }

    /**
     * Get travel expenses for this user (as operator).
     */
    public function travelExpenses(): HasMany
    {
        return $this->hasMany(TravelExpense::class, 'operator_id');
    }

    /**
     * Get attachments uploaded by this user.
     */
    public function uploadedAttachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'uploaded_by');
    }

    /**
     * Get trips assigned to this user (as operator).
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class, 'operator_id');
    }

    /**
     * Get weekly payrolls for this user (as operator).
     */
    public function weeklyPayrolls(): HasMany
    {
        return $this->hasMany(WeeklyPayroll::class, 'operator_id');
    }

    /**
     * Get available roles
     */
    public static function getAvailableRoles(): array
    {
        return [
            'super_admin' => 'Super Administrador',
            'administrador' => 'Administrador',
            'supervisor' => 'Supervisor',
            'contador' => 'Contador',
            'operador' => 'Operador',
        ];
    }

    /**
     * Scope to get only workshop/mechanic users (supervisors)
     */
    public function scopeWorkshopUsers($query)
    {
        return $query->whereIn('role', ['super_admin', 'administrador', 'supervisor']);
    }

    /**
     * Scope to get only operators
     */
    public function scopeOperators($query)
    {
        return $query->where('role', 'operador');
    }

    /**
     * Scope to get only accountants
     */
    public function scopeAccountants($query)
    {
        return $query->whereIn('role', ['super_admin', 'administrador', 'contador']);
    }

    /**
     * Scope to get only active operators
     */
    public function scopeActiveOperators($query)
    {
        return $query->where('role', 'operador')
                    ->where('status', 'active');
    }

    /**
     * Scope to get inactive operators
     */
    public function scopeInactiveOperators($query)
    {
        return $query->where('role', 'operador')
                    ->where('status', 'inactive');
    }

    /**
     * Get the display name for the operator.
     * Returns name with license number for operators, just name for others.
     */
    public function getOperatorDisplayName(): string
    {
        if ($this->isOperator() && $this->license_number) {
            return "{$this->name} ({$this->license_number})";
        }
        return $this->name;
    }

    /**
     * Check if operator is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if operator is inactive.
     */
    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    /**
     * Check if operator is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Get active trips for this operator.
     */
    public function getActiveTrips()
    {
        return $this->trips()->whereIn('status', ['planned', 'in_progress'])->get();
    }

    /**
     * Get completed trips for this operator.
     */
    public function getCompletedTrips()
    {
        return $this->trips()->where('status', 'completed')->get();
    }
}
