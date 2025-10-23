<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AutoAssignmentService
{
    /**
     * Configuration for auto-assignable fields by model and role
     */
    private array $fieldRules = [
        'App\Models\MaintenanceRecord' => [
            'mechanic_id' => [
                'roles' => ['workshop'],
                'auto_assign' => true,
                'hide_for_roles' => ['workshop'],
            ],
        ],
        'App\Models\TravelExpense' => [
            'operator_id' => [
                'roles' => ['operator'],
                'auto_assign' => true,
                'hide_for_roles' => ['operator'],
            ],
        ],
        'App\Models\ProductUsage' => [
            'used_by' => [
                'roles' => ['workshop'],
                'auto_assign' => true,
                'hide_for_roles' => ['workshop'],
            ],
        ],
        'App\Models\ProductRequest' => [
            'requested_by' => [
                'roles' => ['workshop'],
                'auto_assign' => true,
                'hide_for_roles' => ['workshop'],
            ],
        ],
    ];

    /**
     * Determine if a field should be auto-assigned for the given user
     */
    public function shouldAutoAssign(string $modelClass, string $field, User $user): bool
    {
        $rules = $this->getFieldRules($modelClass, $field);
        
        if (!$rules || !($rules['auto_assign'] ?? false)) {
            return false;
        }

        $userRole = $user->role;
        $allowedRoles = $rules['roles'] ?? [];

        return in_array($userRole, $allowedRoles);
    }

    /**
     * Get the auto-assigned value for a field based on user context
     */
    public function getAutoAssignedValue(string $modelClass, string $field, User $user): mixed
    {
        if (!$this->shouldAutoAssign($modelClass, $field, $user)) {
            return null;
        }

        // For user ID fields, return the authenticated user's ID
        if (str_ends_with($field, '_id') || in_array($field, ['used_by', 'requested_by'])) {
            return $user->id;
        }

        return null;
    }

    /**
     * Determine if a field should be hidden for the given user role
     */
    public function hideFieldForRole(string $modelClass, string $field, string $role): bool
    {
        $rules = $this->getFieldRules($modelClass, $field);
        
        if (!$rules) {
            return false;
        }

        $hideForRoles = $rules['hide_for_roles'] ?? [];
        
        return in_array($role, $hideForRoles);
    }

    /**
     * Get all auto-assignable fields for a model and user
     */
    public function getAutoAssignableFields(string $modelClass, User $user): array
    {
        $modelRules = $this->fieldRules[$modelClass] ?? [];
        $autoAssignableFields = [];

        foreach ($modelRules as $field => $rules) {
            if ($this->shouldAutoAssign($modelClass, $field, $user)) {
                $autoAssignableFields[$field] = $this->getAutoAssignedValue($modelClass, $field, $user);
            }
        }

        return $autoAssignableFields;
    }

    /**
     * Get fields that should be hidden for a user role
     */
    public function getHiddenFields(string $modelClass, string $role): array
    {
        $modelRules = $this->fieldRules[$modelClass] ?? [];
        $hiddenFields = [];

        foreach ($modelRules as $field => $rules) {
            if ($this->hideFieldForRole($modelClass, $field, $role)) {
                $hiddenFields[] = $field;
            }
        }

        return $hiddenFields;
    }

    /**
     * Get default values for auto-assignable fields for the current user
     */
    public function getDefaultValues(string $modelClass, ?User $user = null): array
    {
        $user = $user ?? Auth::user();
        
        if (!$user) {
            return [];
        }

        return $this->getAutoAssignableFields($modelClass, $user);
    }

    /**
     * Get field rules for a specific model and field
     */
    private function getFieldRules(string $modelClass, string $field): ?array
    {
        return $this->fieldRules[$modelClass][$field] ?? null;
    }

    /**
     * Add or update field rules for a model
     */
    public function setFieldRules(string $modelClass, string $field, array $rules): void
    {
        if (!isset($this->fieldRules[$modelClass])) {
            $this->fieldRules[$modelClass] = [];
        }

        $this->fieldRules[$modelClass][$field] = $rules;
    }

    /**
     * Get all configured models
     */
    public function getConfiguredModels(): array
    {
        return array_keys($this->fieldRules);
    }
}