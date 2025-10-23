<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

interface FormFieldResolverInterface
{
    /**
     * Resolve form fields for a specific user and operation
     */
    public function resolveFieldsForUser(User $user, string $modelClass, string $operation = 'create'): array;

    /**
     * Get default values for auto-assignable fields
     */
    public function getDefaultValues(User $user, string $modelClass): array;

    /**
     * Get fields that should be hidden for the user
     */
    public function getHiddenFields(User $user, string $modelClass): array;

    /**
     * Get fields that should be disabled for the user
     */
    public function getDisabledFields(User $user, string $modelClass, ?Model $record = null): array;

    /**
     * Get validation rules for fields based on user context
     */
    public function getValidationRules(User $user, string $modelClass, string $operation = 'create'): array;

    /**
     * Check if a specific field should be visible for the user
     */
    public function isFieldVisible(User $user, string $modelClass, string $field): bool;

    /**
     * Check if a specific field should be editable for the user
     */
    public function isFieldEditable(User $user, string $modelClass, string $field, ?Model $record = null): bool;

    /**
     * Get field configuration for a specific model and user
     */
    public function getFieldConfiguration(User $user, string $modelClass): array;

    /**
     * Get comprehensive field information including help text and restrictions
     */
    public function getFieldWithHelp(User $user, string $modelClass, string $field): array;

    /**
     * Get contextual help text for a field based on user role
     */
    public function getFieldHelpText(User $user, string $modelClass, string $field): ?string;

    /**
     * Get the reason why a field is restricted (hidden or disabled)
     */
    public function getRestrictionReason(User $user, string $modelClass, string $field): ?string;
}