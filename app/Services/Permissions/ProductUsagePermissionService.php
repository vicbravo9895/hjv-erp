<?php

namespace App\Services\Permissions;

use App\Models\User;
use App\Services\EnhancedPermissionService;
use Illuminate\Database\Eloquent\Model;

class ProductUsagePermissionService extends EnhancedPermissionService
{
    /**
     * Get fields that should be hidden from the user.
     */
    public function getHiddenFields(User $user): array
    {
        return $this->getHiddenFieldsForModel($user, 'ProductUsage');
    }

    /**
     * Determine whether the user can view any models.
     */
    public function canViewAny(User $user): bool
    {
        // Admin and workshop users can view product usage records
        return $user->hasAdminAccess() || $user->hasWorkshopAccess();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function canView(User $user, Model $record): bool
    {
        // Admin and workshop users can view all product usage records
        return $user->hasAdminAccess() || $user->hasWorkshopAccess();
    }

    /**
     * Determine whether the user can create models.
     */
    public function canCreate(User $user): bool
    {
        // Admin and workshop users can create product usage records
        return $user->hasAdminAccess() || $user->hasWorkshopAccess();
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

        // Workshop users can edit records they created
        if ($user->hasWorkshopAccess()) {
            return $record->used_by === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function canDelete(User $user, Model $record): bool
    {
        // Same logic as edit for product usage
        return $this->canEdit($user, $record);
    }

    /**
     * Determine if a specific field can be edited by the user.
     */
    public function canEditField(User $user, Model $record, string $field): bool
    {
        // Used_by field can only be edited by admin users
        if ($field === 'used_by') {
            return $user->hasAdminAccess();
        }

        // Workshop users can edit their own records
        if ($user->hasWorkshopAccess()) {
            return $record->used_by === $user->id || $user->hasAdminAccess();
        }

        // Admin users can edit all fields
        return $user->hasAdminAccess();
    }

    /**
     * Get status-based restrictions for the user and record.
     */
    public function getStatusRestrictions(User $user, Model $record): array
    {
        $restrictions = [];

        // Check if the associated maintenance record is too old
        if ($record->maintenanceRecord && !$record->maintenanceRecord->canAcceptProductUsage()) {
            $restrictions[] = 'Associated maintenance record is too old to accept product usage modifications';
        }

        return $restrictions;
    }

    /**
     * Determine if the record is in a final state that restricts modifications.
     */
    public function isInFinalState(Model $record): bool
    {
        // Product usage records don't have final states currently
        return false;
    }
}