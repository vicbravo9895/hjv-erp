<?php

namespace App\Traits;

use App\Services\AutoAssignmentService;
use Illuminate\Support\Facades\Auth;

trait HasAutoAssignment
{
    /**
     * Boot the HasAutoAssignment trait
     */
    protected static function bootHasAutoAssignment(): void
    {
        static::creating(function ($model) {
            $model->applyAutoAssignments();
        });
    }

    /**
     * Apply auto-assignments to the model based on current user context
     */
    public function applyAutoAssignments(): void
    {
        $user = Auth::user();
        
        if (!$user) {
            return;
        }

        $autoAssignmentService = app(AutoAssignmentService::class);
        $modelClass = get_class($this);
        
        $defaultValues = $autoAssignmentService->getDefaultValues($modelClass, $user);
        
        foreach ($defaultValues as $field => $value) {
            // Only set the value if it's not already set
            if (is_null($this->getAttribute($field))) {
                $this->setAttribute($field, $value);
            }
        }
    }

    /**
     * Get auto-assignable fields for this model and current user
     */
    public function getAutoAssignableFields(): array
    {
        $user = Auth::user();
        
        if (!$user) {
            return [];
        }

        $autoAssignmentService = app(AutoAssignmentService::class);
        $modelClass = get_class($this);
        
        return $autoAssignmentService->getAutoAssignableFields($modelClass, $user);
    }

    /**
     * Get fields that should be hidden for the current user's role
     */
    public function getHiddenFields(): array
    {
        $user = Auth::user();
        
        if (!$user) {
            return [];
        }

        $autoAssignmentService = app(AutoAssignmentService::class);
        $modelClass = get_class($this);
        
        return $autoAssignmentService->getHiddenFields($modelClass, $user->role);
    }

    /**
     * Check if a field should be auto-assigned for the current user
     */
    public function shouldAutoAssignField(string $field): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        $autoAssignmentService = app(AutoAssignmentService::class);
        $modelClass = get_class($this);
        
        return $autoAssignmentService->shouldAutoAssign($modelClass, $field, $user);
    }

    /**
     * Check if a field should be hidden for the current user's role
     */
    public function shouldHideField(string $field): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }

        $autoAssignmentService = app(AutoAssignmentService::class);
        $modelClass = get_class($this);
        
        return $autoAssignmentService->hideFieldForRole($modelClass, $field, $user->role);
    }

    /**
     * Get the auto-assigned value for a specific field
     */
    public function getAutoAssignedValue(string $field): mixed
    {
        $user = Auth::user();
        
        if (!$user) {
            return null;
        }

        $autoAssignmentService = app(AutoAssignmentService::class);
        $modelClass = get_class($this);
        
        return $autoAssignmentService->getAutoAssignedValue($modelClass, $field, $user);
    }

    /**
     * Force apply auto-assignments (useful for testing or manual operations)
     */
    public function forceAutoAssignments(): void
    {
        $this->applyAutoAssignments();
    }
}