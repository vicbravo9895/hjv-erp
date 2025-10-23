<?php

namespace App\Services\Permissions;

use App\Models\User;
use App\Services\EnhancedPermissionService;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRecordPermissionService extends EnhancedPermissionService
{
    /**
     * Get fields that should be hidden from the user.
     */
    public function getHiddenFields(User $user): array
    {
        return $this->getHiddenFieldsForModel($user, 'MaintenanceRecord');
    }

    /**
     * Determine whether the user can view any models.
     */
    public function canViewAny(User $user): bool
    {
        // Admin and workshop users can view maintenance records
        return $user->hasAdminAccess() || $user->hasWorkshopAccess();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function canView(User $user, Model $record): bool
    {
        // Admin and workshop users can view all maintenance records
        return $user->hasAdminAccess() || $user->hasWorkshopAccess();
    }

    /**
     * Determine whether the user can create models.
     */
    public function canCreate(User $user): bool
    {
        // Admin and workshop users can create maintenance records
        return $user->hasAdminAccess() || $user->hasWorkshopAccess();
    }

    /**
     * Determine whether the user can edit the model.
     */
    public function canEdit(User $user, Model $record): bool
    {
        // Admin and workshop users can edit maintenance records
        return $user->hasAdminAccess() || $user->hasWorkshopAccess();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function canDelete(User $user, Model $record): bool
    {
        // Admin and workshop users can delete maintenance records
        return $user->hasAdminAccess() || $user->hasWorkshopAccess();
    }

    /**
     * Determine if a specific field can be edited by the user.
     */
    public function canEditField(User $user, Model $record, string $field): bool
    {
        // Mechanic ID can only be edited by admin users
        if ($field === 'mechanic_id') {
            return $user->hasAdminAccess();
        }

        // All other fields can be edited by admin and workshop users
        return $user->hasAdminAccess() || $user->hasWorkshopAccess();
    }

    /**
     * Get status-based restrictions for the user and record.
     */
    public function getStatusRestrictions(User $user, Model $record): array
    {
        $restrictions = [];

        // Maintenance records don't have status restrictions currently
        // This can be extended if status workflow is added later

        return $restrictions;
    }

    /**
     * Determine if the record is in a final state that restricts modifications.
     */
    public function isInFinalState(Model $record): bool
    {
        // Maintenance records don't have final states currently
        return false;
    }
}