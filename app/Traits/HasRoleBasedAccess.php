<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait HasRoleBasedAccess
{
    /**
     * Check if the current user can view any records
     */
    public static function canViewAny(): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        // Super admin can access everything
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check specific permissions based on resource type
        return static::checkResourceAccess($user, 'viewAny');
    }

    /**
     * Check if the current user can view this record
     */
    public static function canView(Model $record): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        // Super admin can access everything
        if ($user->isSuperAdmin()) {
            return true;
        }

        return static::checkResourceAccess($user, 'view', $record);
    }

    /**
     * Check if the current user can create records
     */
    public static function canCreate(): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        // Super admin can access everything
        if ($user->isSuperAdmin()) {
            return true;
        }

        return static::checkResourceAccess($user, 'create');
    }

    /**
     * Check if the current user can edit this record
     */
    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        // Super admin can access everything
        if ($user->isSuperAdmin()) {
            return true;
        }

        return static::checkResourceAccess($user, 'edit', $record);
    }

    /**
     * Check if the current user can delete this record
     */
    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        // Super admin can access everything
        if ($user->isSuperAdmin()) {
            return true;
        }

        return static::checkResourceAccess($user, 'delete', $record);
    }

    /**
     * Check resource-specific access based on user role
     * This method should be overridden in each resource that uses this trait
     */
    protected static function checkResourceAccess($user, string $action, ?Model $record = null): bool
    {
        // Default implementation - allow administrators and supervisors full access
        return $user->hasAnyRole(['administrador', 'supervisor']);
    }
}