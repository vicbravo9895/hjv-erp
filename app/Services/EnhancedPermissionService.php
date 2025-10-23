<?php

namespace App\Services;

use App\Contracts\EnhancedPermissionInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EnhancedPermissionService implements EnhancedPermissionInterface
{
    /**
     * Role hierarchy for permission checking.
     */
    protected array $roleHierarchy = [
        'super_admin' => 100,
        'administrador' => 90,
        'supervisor' => 80,
        'contador' => 70,
        'operador' => 10,
    ];

    /**
     * Status fields that indicate final states for different models.
     */
    protected array $finalStates = [
        'TravelExpense' => ['approved', 'reimbursed'],
        'ProductRequest' => ['received'],
        'MaintenanceRecord' => [], // No final states for maintenance records
        'ProductUsage' => [], // No final states for product usage
    ];

    /**
     * Fields that should be hidden by role for different models.
     */
    protected array $hiddenFieldsByRole = [
        'TravelExpense' => [
            'operador' => ['status', 'operator_id'],
        ],
        'MaintenanceRecord' => [
            'workshop' => ['mechanic_id'],
        ],
        'ProductUsage' => [
            'workshop' => ['used_by'],
        ],
        'ProductRequest' => [
            'workshop' => ['requested_by'],
        ],
    ];

    /**
     * Fields that can only be edited by specific roles.
     */
    protected array $restrictedFields = [
        'TravelExpense' => [
            'status' => ['super_admin', 'administrador', 'supervisor', 'contador'],
            'operator_id' => ['super_admin', 'administrador', 'supervisor'],
        ],
        'MaintenanceRecord' => [
            'mechanic_id' => ['super_admin', 'administrador', 'supervisor'],
        ],
        'ProductUsage' => [
            'used_by' => ['super_admin', 'administrador', 'supervisor'],
        ],
        'ProductRequest' => [
            'requested_by' => ['super_admin', 'administrador', 'supervisor'],
            'approved_by' => ['super_admin', 'administrador', 'supervisor'],
            'status' => ['super_admin', 'administrador', 'supervisor'],
        ],
    ];

    /**
     * Determine whether the user can view any models.
     */
    public function canViewAny(User $user): bool
    {
        // All authenticated users can view models (filtered by query scopes)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function canView(User $user, Model $record): bool
    {
        // Admin users can view all records
        if ($user->hasAdminAccess()) {
            return true;
        }

        // Model-specific view permissions
        return $this->checkModelSpecificViewPermission($user, $record);
    }

    /**
     * Determine whether the user can create models.
     */
    public function canCreate(User $user): bool
    {
        // All authenticated users can create records (with field restrictions)
        return true;
    }

    /**
     * Determine whether the user can edit the model.
     */
    public function canEdit(User $user, Model $record): bool
    {
        // Admin users can edit all records
        if ($user->hasAdminAccess()) {
            return true;
        }

        // Check if record is in final state
        if ($this->isInFinalState($record)) {
            return false;
        }

        // Model-specific edit permissions
        return $this->checkModelSpecificEditPermission($user, $record);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function canDelete(User $user, Model $record): bool
    {
        // Admin users can delete all records
        if ($user->hasAdminAccess()) {
            return true;
        }

        // Check if record is in final state
        if ($this->isInFinalState($record)) {
            return false;
        }

        // Model-specific delete permissions (same as edit for most cases)
        return $this->checkModelSpecificEditPermission($user, $record);
    }

    /**
     * Get fields that should be editable for the user and record.
     */
    public function getEditableFields(User $user, Model $record): array
    {
        $modelClass = class_basename($record);
        $allFields = $this->getAllModelFields($modelClass);
        $editableFields = [];

        foreach ($allFields as $field) {
            if ($this->canEditField($user, $record, $field)) {
                $editableFields[] = $field;
            }
        }

        return $editableFields;
    }

    /**
     * Get fields that should be hidden from the user.
     */
    public function getHiddenFields(User $user): array
    {
        // This method is called without a specific model, so we need a model class
        // This will be overridden in model-specific implementations
        return [];
    }

    /**
     * Get fields that should be hidden from the user for a specific model.
     */
    public function getHiddenFieldsForModel(User $user, string $modelClass): array
    {
        $hiddenFields = $this->hiddenFieldsByRole[$modelClass] ?? [];
        return $hiddenFields[$user->role] ?? [];
    }

    /**
     * Determine if a specific field can be edited by the user.
     */
    public function canEditField(User $user, Model $record, string $field): bool
    {
        $modelClass = class_basename($record);

        // Check if field is restricted to specific roles
        $restrictedFields = $this->restrictedFields[$modelClass] ?? [];
        if (isset($restrictedFields[$field])) {
            $allowedRoles = $restrictedFields[$field];
            return in_array($user->role, $allowedRoles);
        }

        // Check if field is hidden for this role
        $hiddenFields = $this->getHiddenFieldsForModel($user, $modelClass);
        if (in_array($field, $hiddenFields)) {
            return false;
        }

        // Check if record is in final state
        if ($this->isInFinalState($record)) {
            return $user->hasAdminAccess();
        }

        return true;
    }

    /**
     * Get status-based restrictions for the user and record.
     */
    public function getStatusRestrictions(User $user, Model $record): array
    {
        $restrictions = [];

        if ($this->isInFinalState($record)) {
            $restrictions[] = 'Record is in final state and cannot be modified';
        }

        // Add model-specific restrictions
        $modelRestrictions = $this->getModelSpecificRestrictions($user, $record);
        $restrictions = array_merge($restrictions, $modelRestrictions);

        return $restrictions;
    }

    /**
     * Determine if the record is in a final state that restricts modifications.
     */
    public function isInFinalState(Model $record): bool
    {
        $modelClass = class_basename($record);
        $finalStates = $this->finalStates[$modelClass] ?? [];

        if (empty($finalStates) || !isset($record->status)) {
            return false;
        }

        return in_array($record->status, $finalStates);
    }

    /**
     * Check model-specific view permissions.
     */
    protected function checkModelSpecificViewPermission(User $user, Model $record): bool
    {
        $modelClass = class_basename($record);

        switch ($modelClass) {
            case 'TravelExpense':
                // Operators can only view their own expenses
                if ($user->isOperator()) {
                    return $record->operator_id === $user->id;
                }
                // Accountants can view all travel expenses
                if ($user->isAccountant()) {
                    return true;
                }
                break;

            case 'MaintenanceRecord':
                // Workshop users can view all maintenance records
                if ($user->hasWorkshopAccess()) {
                    return true;
                }
                break;

            case 'ProductUsage':
            case 'ProductRequest':
                // Workshop users can view all product usage/requests
                if ($user->hasWorkshopAccess()) {
                    return true;
                }
                break;
        }

        return false;
    }

    /**
     * Check model-specific edit permissions.
     */
    protected function checkModelSpecificEditPermission(User $user, Model $record): bool
    {
        $modelClass = class_basename($record);

        switch ($modelClass) {
            case 'TravelExpense':
                // Operators can only edit their own pending expenses
                if ($user->isOperator()) {
                    return $record->operator_id === $user->id && $record->status === 'pending';
                }
                // Accountants can edit all travel expenses
                if ($user->isAccountant()) {
                    return true;
                }
                break;

            case 'MaintenanceRecord':
                // Workshop users can edit maintenance records
                if ($user->hasWorkshopAccess()) {
                    return true;
                }
                break;

            case 'ProductUsage':
            case 'ProductRequest':
                // Workshop users can edit product usage/requests
                if ($user->hasWorkshopAccess()) {
                    return true;
                }
                break;
        }

        return false;
    }

    /**
     * Get model-specific restrictions.
     */
    protected function getModelSpecificRestrictions(User $user, Model $record): array
    {
        $restrictions = [];
        $modelClass = class_basename($record);

        switch ($modelClass) {
            case 'TravelExpense':
                if ($user->isOperator() && $record->status !== 'pending') {
                    $restrictions[] = 'Operators can only modify pending expenses';
                }
                break;

            case 'ProductRequest':
                if ($record->status === 'received') {
                    $restrictions[] = 'Received requests cannot be modified';
                }
                break;
        }

        return $restrictions;
    }

    /**
     * Get all fields for a model class.
     */
    protected function getAllModelFields(string $modelClass): array
    {
        // Define fields for each model
        $modelFields = [
            'TravelExpense' => [
                'trip_id', 'operator_id', 'expense_type', 'amount', 'date',
                'location', 'description', 'fuel_liters', 'fuel_price_per_liter',
                'odometer_reading', 'status'
            ],
            'MaintenanceRecord' => [
                'vehicle_id', 'vehicle_type', 'maintenance_type', 'date',
                'cost', 'description', 'mechanic_id'
            ],
            'ProductUsage' => [
                'spare_part_id', 'maintenance_record_id', 'quantity_used',
                'date_used', 'notes', 'used_by'
            ],
            'ProductRequest' => [
                'spare_part_id', 'quantity_requested', 'priority',
                'justification', 'status', 'requested_by', 'approved_by'
            ],
        ];

        return $modelFields[$modelClass] ?? [];
    }

    /**
     * Check if user has higher role level than required.
     */
    protected function hasRoleLevel(User $user, int $requiredLevel): bool
    {
        $userLevel = $this->roleHierarchy[$user->role] ?? 0;
        return $userLevel >= $requiredLevel;
    }

    /**
     * Get role level for a user.
     */
    protected function getRoleLevel(User $user): int
    {
        return $this->roleHierarchy[$user->role] ?? 0;
    }
}