<?php

namespace App\Services\Permissions;

use App\Models\User;
use App\Services\EnhancedPermissionService;
use Illuminate\Database\Eloquent\Model;

class ProductRequestPermissionService extends EnhancedPermissionService
{
    /**
     * Get fields that should be hidden from the user.
     */
    public function getHiddenFields(User $user): array
    {
        return $this->getHiddenFieldsForModel($user, 'ProductRequest');
    }

    /**
     * Determine whether the user can view any models.
     */
    public function canViewAny(User $user): bool
    {
        // Admin and workshop users can view product requests
        return $user->hasAdminAccess() || $user->hasWorkshopAccess();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function canView(User $user, Model $record): bool
    {
        // Admin and workshop users can view all product requests
        if ($user->hasAdminAccess() || $user->hasWorkshopAccess()) {
            return true;
        }

        // Users can view requests they created
        return $record->requested_by === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function canCreate(User $user): bool
    {
        // Admin and workshop users can create product requests
        return $user->hasAdminAccess() || $user->hasWorkshopAccess();
    }

    /**
     * Determine whether the user can edit the model.
     */
    public function canEdit(User $user, Model $record): bool
    {
        // Admin users can edit all requests
        if ($user->hasAdminAccess()) {
            return true;
        }

        // Workshop users can edit pending requests they created
        if ($user->hasWorkshopAccess()) {
            return $record->requested_by === $user->id && $record->status === 'pending';
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function canDelete(User $user, Model $record): bool
    {
        // Admin users can delete all requests
        if ($user->hasAdminAccess()) {
            return true;
        }

        // Workshop users can delete pending requests they created
        if ($user->hasWorkshopAccess()) {
            return $record->requested_by === $user->id && $record->status === 'pending';
        }

        return false;
    }

    /**
     * Determine if a specific field can be edited by the user.
     */
    public function canEditField(User $user, Model $record, string $field): bool
    {
        // Status, approved_by, and requested_by can only be edited by admin users
        if (in_array($field, ['status', 'approved_by', 'requested_by'])) {
            return $user->hasAdminAccess();
        }

        // Workshop users can edit their own pending requests
        if ($user->hasWorkshopAccess()) {
            return $record->requested_by === $user->id && $record->status === 'pending';
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

        if (!$user->hasAdminAccess()) {
            switch ($record->status) {
                case 'approved':
                    $restrictions[] = 'Approved requests can only be modified by administrators';
                    break;
                case 'ordered':
                    $restrictions[] = 'Ordered requests can only be modified by administrators';
                    break;
                case 'received':
                    $restrictions[] = 'Received requests cannot be modified';
                    break;
            }
        }

        return $restrictions;
    }

    /**
     * Determine if the record is in a final state that restricts modifications.
     */
    public function isInFinalState(Model $record): bool
    {
        // Received requests are in final state
        return $record->status === 'received';
    }

    /**
     * Check if user can approve the request.
     */
    public function canApprove(User $user, Model $record): bool
    {
        return $user->hasAdminAccess() && $record->status === 'pending';
    }

    /**
     * Check if user can mark as ordered.
     */
    public function canMarkAsOrdered(User $user, Model $record): bool
    {
        return $user->hasAdminAccess() && $record->status === 'approved';
    }

    /**
     * Check if user can mark as received.
     */
    public function canMarkAsReceived(User $user, Model $record): bool
    {
        return $user->hasAdminAccess() && $record->status === 'ordered';
    }

    /**
     * Check if user can reject the request.
     */
    public function canReject(User $user, Model $record): bool
    {
        return $user->hasAdminAccess() && in_array($record->status, ['approved', 'ordered']);
    }
}