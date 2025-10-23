<?php

namespace App\Filament\Forms\Components;

use App\Contracts\FormFieldResolverInterface;
use App\Models\User;
use Filament\Forms\Components\Component;
use Illuminate\Support\Facades\Auth;

/**
 * Trait to add contextual help to any Filament form field
 */
trait ContextualHelpField
{
    /**
     * Add contextual help based on user role and field configuration
     */
    public function withContextualHelp(string $modelClass, string $fieldName): static
    {
        $user = Auth::user();
        if (!$user instanceof User) {
            return $this;
        }

        $resolver = app(FormFieldResolverInterface::class);
        $fieldInfo = $resolver->getFieldWithHelp($user, $modelClass, $fieldName);

        // Add help text if available
        if ($fieldInfo['helpText']) {
            $this->helperText($fieldInfo['helpText']);
        }

        // Add hint for auto-assigned fields
        if ($fieldInfo['autoAssigned']) {
            $this->hint('Asignado automáticamente')
                ->hintIcon('heroicon-o-information-circle')
                ->hintColor('info');
        }

        // Add hint for required fields
        if ($fieldInfo['required']) {
            $this->required();
        }

        return $this;
    }

    /**
     * Add role-level indicator badge
     */
    public function withRoleIndicator(array $allowedRoles): static
    {
        $user = Auth::user();
        if (!$user instanceof User) {
            return $this;
        }

        if (!in_array($user->role, $allowedRoles) && !in_array('*', $allowedRoles)) {
            $resolver = app(FormFieldResolverInterface::class);
            $roleNames = array_map(
                fn($role) => $this->formatRoleName($role),
                $allowedRoles
            );
            
            $rolesText = count($roleNames) === 1 
                ? $roleNames[0] 
                : implode(', ', array_slice($roleNames, 0, -1)) . ' y ' . end($roleNames);

            $this->hint("Solo para: {$rolesText}")
                ->hintIcon('heroicon-o-lock-closed')
                ->hintColor('warning');
        }

        return $this;
    }

    /**
     * Format role name into human-readable text
     */
    protected function formatRoleName(string $role): string
    {
        $roleNames = [
            'super_admin' => 'Super Admin',
            'administrador' => 'Admin',
            'supervisor' => 'Supervisor',
            'contador' => 'Contador',
            'operador' => 'Operador',
            'workshop' => 'Taller',
        ];

        return $roleNames[$role] ?? ucfirst($role);
    }
}
