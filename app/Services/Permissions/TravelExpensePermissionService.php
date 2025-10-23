<?php

namespace App\Services\Permissions;

use App\Models\User;
use App\Services\EnhancedPermissionService;
use Illuminate\Database\Eloquent\Model;

class TravelExpensePermissionService extends EnhancedPermissionService
{
    /**
     * Get fields that should be hidden from the user.
     */
    public function getHiddenFields(User $user): array
    {
        return $this->getHiddenFieldsForModel($user, 'TravelExpense');
    }

    /**
     * Determine whether the user can view any models.
     */
    public function canViewAny(User $user): bool
    {
        // Operators, admin, and accounting users can view travel expenses
        return $user->isOperator() || $user->hasAdminAccess() || $user->hasAccountingAccess();
    }

    /**
     * Determine whether the user can create models.
     */
    public function canCreate(User $user): bool
    {
        // Operators, admin, and accounting users can create travel expenses
        return $user->isOperator() || $user->hasAdminAccess() || $user->hasAccountingAccess();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function canView(User $user, Model $record): bool
    {
        // Admin and accounting users can view all expenses
        if ($user->hasAdminAccess() || $user->hasAccountingAccess()) {
            return true;
        }

        // Operators can only view their own expenses
        if ($user->isOperator()) {
            return $record->operator_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can edit the model.
     */
    public function canEdit(User $user, Model $record): bool
    {
        // Admin and accounting users can edit all expenses
        if ($user->hasAdminAccess() || $user->hasAccountingAccess()) {
            return true;
        }

        // Operators can only edit their own pending expenses
        if ($user->isOperator()) {
            return $record->operator_id === $user->id && $record->status === 'pending';
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function canDelete(User $user, Model $record): bool
    {
        // Same logic as edit for travel expenses
        return $this->canEdit($user, $record);
    }

    /**
     * Determine if a specific field can be edited by the user.
     */
    public function canEditField(User $user, Model $record, string $field): bool
    {
        // Status field can only be edited by admin/accounting users
        if ($field === 'status') {
            return $user->hasAdminAccess() || $user->hasAccountingAccess();
        }

        // Operator ID can only be edited by admin users
        if ($field === 'operator_id') {
            return $user->hasAdminAccess();
        }

        // Operators can only edit if expense is pending
        if ($user->isOperator()) {
            return $record->status === 'pending';
        }

        // Admin and accounting users can edit all other fields
        return $user->hasAdminAccess() || $user->hasAccountingAccess();
    }

    /**
     * Get status-based restrictions for the user and record.
     */
    public function getStatusRestrictions(User $user, Model $record): array
    {
        $restrictions = parent::getStatusRestrictions($user, $record);

        if ($user->isOperator()) {
            if ($record->status === 'approved') {
                $restrictions[] = 'Approved expenses cannot be modified by operators';
            } elseif ($record->status === 'reimbursed') {
                $restrictions[] = 'Reimbursed expenses cannot be modified by operators';
            }
        }

        return $restrictions;
    }

    /**
     * Determine if the record is in a final state that restricts modifications.
     */
    public function isInFinalState(Model $record): bool
    {
        // For operators, approved and reimbursed are final states
        // For admin/accounting, only reimbursed might be considered final
        return in_array($record->status, ['approved', 'reimbursed']);
    }
}