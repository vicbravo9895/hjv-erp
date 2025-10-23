<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AttachmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Attachment $attachment): bool
    {
        // Admin users can view all attachments
        if ($user->hasAdminAccess()) {
            return true;
        }

        // Check if the attachment belongs to a travel expense
        if ($attachment->attachable_type === 'App\Models\TravelExpense') {
            $travelExpense = $attachment->attachable;
            
            // Operators can only view their own travel expense attachments
            if ($user->isOperator()) {
                return $travelExpense->operator_id === $user->id;
            }
            
            // Accountants can view all travel expense attachments
            if ($user->isAccountant()) {
                return true;
            }
        }

        // Check if user uploaded the attachment
        return $attachment->uploaded_by === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Attachment $attachment): bool
    {
        // Admin users can update all attachments
        if ($user->hasAdminAccess()) {
            return true;
        }

        // Users can only update attachments they uploaded
        return $attachment->uploaded_by === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Attachment $attachment): bool
    {
        // Admin users can delete all attachments
        if ($user->hasAdminAccess()) {
            return true;
        }

        // Check if the attachment belongs to a travel expense
        if ($attachment->attachable_type === 'App\Models\TravelExpense') {
            $travelExpense = $attachment->attachable;
            
            // Operators can only delete their own travel expense attachments if expense is pending
            if ($user->isOperator()) {
                return $travelExpense->operator_id === $user->id && $travelExpense->status === 'pending';
            }
        }

        // Users can only delete attachments they uploaded
        return $attachment->uploaded_by === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Attachment $attachment): bool
    {
        return $user->hasAdminAccess();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Attachment $attachment): bool
    {
        return $user->hasAdminAccess();
    }
}