<?php

namespace App\Services;

use App\Contracts\FormFieldResolverInterface;
use App\Models\User;
use App\Services\AutoAssignmentService;
use App\Services\EnhancedPermissionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class FormFieldResolver implements FormFieldResolverInterface
{
    public function __construct(
        protected AutoAssignmentService $autoAssignmentService,
        protected EnhancedPermissionService $permissionService
    ) {}

    /**
     * Field configuration rules for different models and operations
     */
    protected array $fieldConfigurations = [
        'App\Models\MaintenanceRecord' => [
            'fields' => [
                'vehicle_type' => [
                    'type' => 'select',
                    'required' => true,
                    'visible_for_roles' => ['*'],
                    'editable_for_roles' => ['*'],
                ],
                'vehicle_id' => [
                    'type' => 'select',
                    'required' => true,
                    'visible_for_roles' => ['*'],
                    'editable_for_roles' => ['*'],
                ],
                'maintenance_type' => [
                    'type' => 'select',
                    'required' => true,
                    'visible_for_roles' => ['*'],
                    'editable_for_roles' => ['*'],
                ],
                'date' => [
                    'type' => 'date',
                    'required' => true,
                    'visible_for_roles' => ['*'],
                    'editable_for_roles' => ['*'],
                    'default' => 'now',
                ],
                'cost' => [
                    'type' => 'number',
                    'required' => true,
                    'visible_for_roles' => ['*'],
                    'editable_for_roles' => ['*'],
                ],
                'description' => [
                    'type' => 'textarea',
                    'required' => true,
                    'visible_for_roles' => ['*'],
                    'editable_for_roles' => ['*'],
                ],
                'mechanic_id' => [
                    'type' => 'select',
                    'required' => false,
                    'visible_for_roles' => ['super_admin', 'administrador', 'supervisor'],
                    'editable_for_roles' => ['super_admin', 'administrador', 'supervisor'],
                    'auto_assign_for_roles' => ['workshop'],
                ],
            ],
        ],
        'App\Models\TravelExpense' => [
            'fields' => [
                'trip_id' => [
                    'type' => 'select',
                    'required' => true,
                    'visible_for_roles' => ['*'],
                    'editable_for_roles' => ['*'],
                ],
                'operator_id' => [
                    'type' => 'select',
                    'required' => true,
                    'visible_for_roles' => ['super_admin', 'administrador', 'supervisor', 'contador'],
                    'editable_for_roles' => ['super_admin', 'administrador', 'supervisor'],
                    'auto_assign_for_roles' => ['operador'],
                ],
                'expense_type' => [
                    'type' => 'select',
                    'required' => true,
                    'visible_for_roles' => ['*'],
                    'editable_for_roles' => ['*'],
                ],
                'amount' => [
                    'type' => 'number',
                    'required' => true,
                    'visible_for_roles' => ['*'],
                    'editable_for_roles' => ['*'],
                ],
                'date' => [
                    'type' => 'date',
                    'required' => true,
                    'visible_for_roles' => ['*'],
                    'editable_for_roles' => ['*'],
                    'default' => 'now',
                ],
                'location' => [
                    'type' => 'text',
                    'required' => false,
                    'visible_for_roles' => ['*'],
                    'editable_for_roles' => ['*'],
                ],
                'description' => [
                    'type' => 'textarea',
                    'required' => false,
                    'visible_for_roles' => ['*'],
                    'editable_for_roles' => ['*'],
                ],
                'fuel_liters' => [
                    'type' => 'number',
                    'required' => false,
                    'visible_for_roles' => ['*'],
                    'editable_for_roles' => ['*'],
                    'conditional' => ['expense_type' => 'fuel'],
                ],
                'fuel_price_per_liter' => [
                    'type' => 'number',
                    'required' => false,
                    'visible_for_roles' => ['*'],
                    'editable_for_roles' => ['*'],
                    'conditional' => ['expense_type' => 'fuel'],
                ],
                'odometer_reading' => [
                    'type' => 'number',
                    'required' => false,
                    'visible_for_roles' => ['*'],
                    'editable_for_roles' => ['*'],
                    'conditional' => ['expense_type' => 'fuel'],
                ],
                'status' => [
                    'type' => 'select',
                    'required' => true,
                    'visible_for_roles' => ['super_admin', 'administrador', 'supervisor', 'contador'],
                    'editable_for_roles' => ['super_admin', 'administrador', 'supervisor', 'contador'],
                    'default' => 'pending',
                ],
                'attachments' => [
                    'type' => 'file',
                    'required' => false,
                    'visible_for_roles' => ['*'],
                    'editable_for_roles' => ['*'],
                ],
            ],
        ],
        'App\Models\ProductUsage' => [
            'fields' => [
                'spare_part_id' => [
                    'type' => 'select',
                    'required' => true,
                    'visible_for_roles' => ['*'],
                    'editable_for_roles' => ['*'],
                ],
                'maintenance_record_id' => [
                    'type' => 'select',
                    'required' => false,
                    'visible_for_roles' => ['*'],
                    'editable_for_roles' => ['*'],
                ],
                'quantity_used' => [
                    'type' => 'number',
                    'required' => true,
                    'visible_for_roles' => ['*'],
                    'editable_for_roles' => ['*'],
                ],
                'date_used' => [
                    'type' => 'date',
                    'required' => true,
                    'visible_for_roles' => ['*'],
                    'editable_for_roles' => ['*'],
                    'default' => 'now',
                ],
                'notes' => [
                    'type' => 'textarea',
                    'required' => false,
                    'visible_for_roles' => ['*'],
                    'editable_for_roles' => ['*'],
                ],
                'used_by' => [
                    'type' => 'select',
                    'required' => true,
                    'visible_for_roles' => ['super_admin', 'administrador', 'supervisor'],
                    'editable_for_roles' => ['super_admin', 'administrador', 'supervisor'],
                    'auto_assign_for_roles' => ['workshop'],
                ],
            ],
        ],
        'App\Models\ProductRequest' => [
            'fields' => [
                'spare_part_id' => [
                    'type' => 'select',
                    'required' => true,
                    'visible_for_roles' => ['*'],
                    'editable_for_roles' => ['*'],
                ],
                'quantity_requested' => [
                    'type' => 'number',
                    'required' => true,
                    'visible_for_roles' => ['*'],
                    'editable_for_roles' => ['*'],
                ],
                'priority' => [
                    'type' => 'select',
                    'required' => true,
                    'visible_for_roles' => ['*'],
                    'editable_for_roles' => ['*'],
                ],
                'justification' => [
                    'type' => 'textarea',
                    'required' => true,
                    'visible_for_roles' => ['*'],
                    'editable_for_roles' => ['*'],
                ],
                'status' => [
                    'type' => 'select',
                    'required' => true,
                    'visible_for_roles' => ['super_admin', 'administrador', 'supervisor'],
                    'editable_for_roles' => ['super_admin', 'administrador', 'supervisor'],
                    'default' => 'pending',
                ],
                'requested_by' => [
                    'type' => 'select',
                    'required' => true,
                    'visible_for_roles' => ['super_admin', 'administrador', 'supervisor'],
                    'editable_for_roles' => ['super_admin', 'administrador', 'supervisor'],
                    'auto_assign_for_roles' => ['workshop'],
                ],
                'approved_by' => [
                    'type' => 'select',
                    'required' => false,
                    'visible_for_roles' => ['super_admin', 'administrador', 'supervisor'],
                    'editable_for_roles' => ['super_admin', 'administrador', 'supervisor'],
                ],
            ],
        ],
    ];

    /**
     * Resolve form fields for a specific user and operation
     */
    public function resolveFieldsForUser(User $user, string $modelClass, string $operation = 'create'): array
    {
        $configuration = $this->getFieldConfiguration($user, $modelClass);
        $resolvedFields = [];

        foreach ($configuration as $fieldName => $fieldConfig) {
            if ($this->isFieldVisible($user, $modelClass, $fieldName)) {
                $resolvedFields[$fieldName] = [
                    'visible' => true,
                    'editable' => $this->isFieldEditable($user, $modelClass, $fieldName),
                    'required' => $fieldConfig['required'] ?? false,
                    'type' => $fieldConfig['type'] ?? 'text',
                    'default' => $this->getFieldDefaultValue($user, $modelClass, $fieldName),
                    'auto_assigned' => $this->isFieldAutoAssigned($user, $modelClass, $fieldName),
                    'conditional' => $fieldConfig['conditional'] ?? null,
                ];
            }
        }

        return $resolvedFields;
    }

    /**
     * Get default values for auto-assignable fields
     */
    public function getDefaultValues(User $user, string $modelClass): array
    {
        $defaults = [];
        $configuration = $this->getFieldConfiguration($user, $modelClass);

        foreach ($configuration as $fieldName => $fieldConfig) {
            $defaultValue = $this->getFieldDefaultValue($user, $modelClass, $fieldName);
            if ($defaultValue !== null) {
                $defaults[$fieldName] = $defaultValue;
            }
        }

        return $defaults;
    }

    /**
     * Get fields that should be hidden for the user
     */
    public function getHiddenFields(User $user, string $modelClass): array
    {
        $hiddenFields = [];
        $configuration = $this->getFieldConfiguration($user, $modelClass);

        foreach ($configuration as $fieldName => $fieldConfig) {
            if (!$this->isFieldVisible($user, $modelClass, $fieldName)) {
                $hiddenFields[] = $fieldName;
            }
        }

        return $hiddenFields;
    }

    /**
     * Get fields that should be disabled for the user
     */
    public function getDisabledFields(User $user, string $modelClass, ?Model $record = null): array
    {
        $disabledFields = [];
        $configuration = $this->getFieldConfiguration($user, $modelClass);

        foreach ($configuration as $fieldName => $fieldConfig) {
            if (!$this->isFieldEditable($user, $modelClass, $fieldName, $record)) {
                $disabledFields[] = $fieldName;
            }
        }

        return $disabledFields;
    }

    /**
     * Get validation rules for fields based on user context
     */
    public function getValidationRules(User $user, string $modelClass, string $operation = 'create'): array
    {
        $rules = [];
        $configuration = $this->getFieldConfiguration($user, $modelClass);

        foreach ($configuration as $fieldName => $fieldConfig) {
            if ($this->isFieldVisible($user, $modelClass, $fieldName)) {
                $fieldRules = [];

                // Add required rule if field is required and not auto-assigned
                if (($fieldConfig['required'] ?? false) && !$this->isFieldAutoAssigned($user, $modelClass, $fieldName)) {
                    $fieldRules[] = 'required';
                }

                // Add type-specific validation rules
                switch ($fieldConfig['type'] ?? 'text') {
                    case 'number':
                        $fieldRules[] = 'numeric';
                        break;
                    case 'date':
                        $fieldRules[] = 'date';
                        break;
                    case 'email':
                        $fieldRules[] = 'email';
                        break;
                    case 'select':
                        // Add exists validation if it's a relationship field
                        if (str_ends_with($fieldName, '_id')) {
                            $fieldRules[] = 'exists:' . $this->getTableNameFromField($fieldName) . ',id';
                        }
                        break;
                }

                if (!empty($fieldRules)) {
                    $rules[$fieldName] = $fieldRules;
                }
            }
        }

        return $rules;
    }

    /**
     * Check if a specific field should be visible for the user
     */
    public function isFieldVisible(User $user, string $modelClass, string $field): bool
    {
        $configuration = $this->getFieldConfiguration($user, $modelClass);
        $fieldConfig = $configuration[$field] ?? null;

        if (!$fieldConfig) {
            return true; // Default to visible if not configured
        }

        $visibleForRoles = $fieldConfig['visible_for_roles'] ?? ['*'];

        // If '*' is in the array, field is visible for all roles
        if (in_array('*', $visibleForRoles)) {
            return true;
        }

        return in_array($user->role, $visibleForRoles);
    }

    /**
     * Check if a specific field should be editable for the user
     */
    public function isFieldEditable(User $user, string $modelClass, string $field, ?Model $record = null): bool
    {
        // First check if field is visible
        if (!$this->isFieldVisible($user, $modelClass, $field)) {
            return false;
        }

        $configuration = $this->getFieldConfiguration($user, $modelClass);
        $fieldConfig = $configuration[$field] ?? null;

        if (!$fieldConfig) {
            return true; // Default to editable if not configured
        }

        // Check if field is auto-assigned for this user (auto-assigned fields are not editable)
        if ($this->isFieldAutoAssigned($user, $modelClass, $field)) {
            return false;
        }

        // Check role-based editability
        $editableForRoles = $fieldConfig['editable_for_roles'] ?? ['*'];

        // If '*' is in the array, field is editable for all roles
        if (in_array('*', $editableForRoles)) {
            // Still need to check permission service for record-specific restrictions
            if ($record) {
                return $this->permissionService->canEditField($user, $record, $field);
            }
            return true;
        }

        $roleEditable = in_array($user->role, $editableForRoles);

        // If role allows editing, check permission service for record-specific restrictions
        if ($roleEditable && $record) {
            return $this->permissionService->canEditField($user, $record, $field);
        }

        return $roleEditable;
    }

    /**
     * Get field configuration for a specific model and user
     */
    public function getFieldConfiguration(User $user, string $modelClass): array
    {
        return $this->fieldConfigurations[$modelClass]['fields'] ?? [];
    }

    /**
     * Check if a field should be auto-assigned for the user
     */
    protected function isFieldAutoAssigned(User $user, string $modelClass, string $field): bool
    {
        $configuration = $this->getFieldConfiguration($user, $modelClass);
        $fieldConfig = $configuration[$field] ?? null;

        if (!$fieldConfig) {
            return false;
        }

        $autoAssignForRoles = $fieldConfig['auto_assign_for_roles'] ?? [];
        return in_array($user->role, $autoAssignForRoles);
    }

    /**
     * Get the default value for a field
     */
    protected function getFieldDefaultValue(User $user, string $modelClass, string $field): mixed
    {
        // Check if field should be auto-assigned
        if ($this->isFieldAutoAssigned($user, $modelClass, $field)) {
            return $this->autoAssignmentService->getAutoAssignedValue($modelClass, $field, $user);
        }

        // Check for configured default values
        $configuration = $this->getFieldConfiguration($user, $modelClass);
        $fieldConfig = $configuration[$field] ?? null;

        if (!$fieldConfig || !isset($fieldConfig['default'])) {
            return null;
        }

        $default = $fieldConfig['default'];

        // Handle special default values
        if ($default === 'now') {
            return now();
        }

        return $default;
    }

    /**
     * Get table name from field name (for validation rules)
     */
    protected function getTableNameFromField(string $field): string
    {
        // Remove '_id' suffix and pluralize
        $tableName = str_replace('_id', '', $field);
        
        // Handle special cases
        $tableMap = [
            'operator' => 'users',
            'mechanic' => 'users',
            'used_by' => 'users',
            'requested_by' => 'users',
            'approved_by' => 'users',
            'vehicle' => 'vehicles',
            'spare_part' => 'spare_parts',
            'maintenance_record' => 'maintenance_records',
            'trip' => 'trips',
        ];

        return $tableMap[$tableName] ?? $tableName . 's';
    }

    /**
     * Add or update field configuration for a model
     */
    public function setFieldConfiguration(string $modelClass, string $field, array $config): void
    {
        if (!isset($this->fieldConfigurations[$modelClass])) {
            $this->fieldConfigurations[$modelClass] = ['fields' => []];
        }

        $this->fieldConfigurations[$modelClass]['fields'][$field] = $config;
    }

    /**
     * Get all configured models
     */
    public function getConfiguredModels(): array
    {
        return array_keys($this->fieldConfigurations);
    }

    /**
     * Check if a field has conditional visibility
     */
    public function hasConditionalVisibility(string $modelClass, string $field): bool
    {
        $configuration = $this->getFieldConfiguration(Auth::user(), $modelClass);
        $fieldConfig = $configuration[$field] ?? null;

        return isset($fieldConfig['conditional']);
    }

    /**
     * Get conditional visibility rules for a field
     */
    public function getConditionalRules(string $modelClass, string $field): ?array
    {
        $configuration = $this->getFieldConfiguration(Auth::user(), $modelClass);
        $fieldConfig = $configuration[$field] ?? null;

        return $fieldConfig['conditional'] ?? null;
    }

    /**
     * Get comprehensive field information including help text and restrictions
     */
    public function getFieldWithHelp(User $user, string $modelClass, string $field): array
    {
        $isVisible = $this->isFieldVisible($user, $modelClass, $field);
        $isEditable = $this->isFieldEditable($user, $modelClass, $field);
        
        return [
            'visible' => $isVisible,
            'editable' => $isEditable,
            'helpText' => $this->getFieldHelpText($user, $modelClass, $field),
            'restrictionReason' => $this->getRestrictionReason($user, $modelClass, $field),
            'autoAssigned' => $this->isFieldAutoAssigned($user, $modelClass, $field),
            'required' => $this->isFieldRequired($user, $modelClass, $field),
        ];
    }

    /**
     * Get contextual help text for a field based on user role
     */
    public function getFieldHelpText(User $user, string $modelClass, string $field): ?string
    {
        $configuration = $this->getFieldConfiguration($user, $modelClass);
        $fieldConfig = $configuration[$field] ?? null;

        if (!$fieldConfig) {
            return null;
        }

        // Check for role-specific help text
        if (isset($fieldConfig['help_text'])) {
            $helpText = $fieldConfig['help_text'];
            
            // If help text is an array, return role-specific text
            if (is_array($helpText)) {
                return $helpText[$user->role] ?? $helpText['default'] ?? null;
            }
            
            return $helpText;
        }

        // Generate contextual help based on field configuration
        $help = [];

        // Add auto-assignment information
        if ($this->isFieldAutoAssigned($user, $modelClass, $field)) {
            $help[] = 'Este campo se asignará automáticamente según tu usuario.';
        }

        // Add conditional visibility information
        if (isset($fieldConfig['conditional'])) {
            $conditions = $fieldConfig['conditional'];
            $conditionText = $this->formatConditionalText($conditions);
            $help[] = "Este campo es visible cuando: {$conditionText}";
        }

        // Add requirement information
        if ($fieldConfig['required'] ?? false) {
            $help[] = 'Este campo es obligatorio.';
        }

        return !empty($help) ? implode(' ', $help) : null;
    }

    /**
     * Get the reason why a field is restricted (hidden or disabled)
     */
    public function getRestrictionReason(User $user, string $modelClass, string $field): ?string
    {
        $configuration = $this->getFieldConfiguration($user, $modelClass);
        $fieldConfig = $configuration[$field] ?? null;

        if (!$fieldConfig) {
            return null;
        }

        // Check if field is hidden
        if (!$this->isFieldVisible($user, $modelClass, $field)) {
            $visibleForRoles = $fieldConfig['visible_for_roles'] ?? ['*'];
            
            if (!in_array('*', $visibleForRoles)) {
                $roleNames = $this->formatRoleNames($visibleForRoles);
                return "Este campo solo es visible para: {$roleNames}. Tu rol actual ({$this->formatRoleName($user->role)}) no tiene acceso.";
            }
        }

        // Check if field is disabled (visible but not editable)
        if ($this->isFieldVisible($user, $modelClass, $field) && !$this->isFieldEditable($user, $modelClass, $field)) {
            // Check if it's auto-assigned
            if ($this->isFieldAutoAssigned($user, $modelClass, $field)) {
                return 'Este campo se asigna automáticamente y no puede ser editado.';
            }

            // Check role-based editability
            $editableForRoles = $fieldConfig['editable_for_roles'] ?? ['*'];
            
            if (!in_array('*', $editableForRoles)) {
                $roleNames = $this->formatRoleNames($editableForRoles);
                return "Este campo solo puede ser editado por: {$roleNames}. Tu rol actual ({$this->formatRoleName($user->role)}) puede verlo pero no modificarlo.";
            }
        }

        return null;
    }

    /**
     * Check if a field is required for the user
     */
    protected function isFieldRequired(User $user, string $modelClass, string $field): bool
    {
        $configuration = $this->getFieldConfiguration($user, $modelClass);
        $fieldConfig = $configuration[$field] ?? null;

        if (!$fieldConfig) {
            return false;
        }

        // Auto-assigned fields are not required from user input
        if ($this->isFieldAutoAssigned($user, $modelClass, $field)) {
            return false;
        }

        return $fieldConfig['required'] ?? false;
    }

    /**
     * Format conditional rules into human-readable text
     */
    protected function formatConditionalText(array $conditions): string
    {
        $parts = [];
        
        foreach ($conditions as $field => $value) {
            $fieldLabel = $this->formatFieldLabel($field);
            $valueLabel = is_array($value) ? implode(' o ', $value) : $value;
            $parts[] = "{$fieldLabel} es '{$valueLabel}'";
        }

        return implode(' y ', $parts);
    }

    /**
     * Format field name into human-readable label
     */
    protected function formatFieldLabel(string $field): string
    {
        $labels = [
            'expense_type' => 'tipo de gasto',
            'vehicle_type' => 'tipo de vehículo',
            'maintenance_type' => 'tipo de mantenimiento',
            'status' => 'estado',
            'priority' => 'prioridad',
        ];

        return $labels[$field] ?? str_replace('_', ' ', $field);
    }

    /**
     * Format role name into human-readable text
     */
    protected function formatRoleName(string $role): string
    {
        $roleNames = [
            'super_admin' => 'Super Administrador',
            'administrador' => 'Administrador',
            'supervisor' => 'Supervisor',
            'contador' => 'Contador',
            'operador' => 'Operador',
            'workshop' => 'Taller',
        ];

        return $roleNames[$role] ?? ucfirst($role);
    }

    /**
     * Format multiple role names into human-readable text
     */
    protected function formatRoleNames(array $roles): string
    {
        $formattedRoles = array_map(fn($role) => $this->formatRoleName($role), $roles);
        
        if (count($formattedRoles) === 1) {
            return $formattedRoles[0];
        }

        $last = array_pop($formattedRoles);
        return implode(', ', $formattedRoles) . ' y ' . $last;
    }
}