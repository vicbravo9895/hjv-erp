<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

interface EnhancedPermissionInterface
{
    /**
     * Determine whether the user can view any models.
     */
    public function canViewAny(User $user): bool;

    /**
     * Determine whether the user can view the model.
     */
    public function canView(User $user, Model $record): bool;

    /**
     * Determine whether the user can create models.
     */
    public function canCreate(User $user): bool;

    /**
     * Determine whether the user can edit the model.
     */
    public function canEdit(User $user, Model $record): bool;

    /**
     * Determine whether the user can delete the model.
     */
    public function canDelete(User $user, Model $record): bool;

    /**
     * Get fields that should be editable for the user and record.
     */
    public function getEditableFields(User $user, Model $record): array;

    /**
     * Get fields that should be hidden from the user.
     */
    public function getHiddenFields(User $user): array;

    /**
     * Determine if a specific field can be edited by the user.
     */
    public function canEditField(User $user, Model $record, string $field): bool;

    /**
     * Get status-based restrictions for the user and record.
     */
    public function getStatusRestrictions(User $user, Model $record): array;

    /**
     * Determine if the record is in a final state that restricts modifications.
     */
    public function isInFinalState(Model $record): bool;
}