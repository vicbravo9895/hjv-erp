<?php

namespace App\Filament\Forms\Components;

use App\Contracts\FormFieldResolverInterface;
use App\Models\User;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\Facades\Auth;

/**
 * Helper class for building forms with contextual help and field restrictions
 */
class EnhancedFormBuilder
{
    protected FormFieldResolverInterface $resolver;
    protected User $user;
    protected string $modelClass;

    public function __construct(string $modelClass)
    {
        $this->resolver = app(FormFieldResolverInterface::class);
        $this->user = Auth::user();
        $this->modelClass = $modelClass;
    }

    /**
     * Create a new instance for a specific model
     */
    public static function for(string $modelClass): static
    {
        return new static($modelClass);
    }

    /**
     * Wrap a field with contextual help and restriction information
     */
    public function wrapField(Component $field, string $fieldName): Component|array
    {
        $fieldInfo = $this->resolver->getFieldWithHelp($this->user, $this->modelClass, $fieldName);

        // If field is not visible, return a placeholder with explanation
        if (!$fieldInfo['visible']) {
            return $this->createRestrictionPlaceholder($fieldName, $fieldInfo);
        }

        // Add contextual help to the field
        if ($fieldInfo['helpText']) {
            $field->helperText($fieldInfo['helpText']);
        }

        // Add hint for auto-assigned fields
        if ($fieldInfo['autoAssigned']) {
            $field->hint('Asignado automáticamente')
                ->hintIcon('heroicon-o-information-circle')
                ->hintColor('info')
                ->disabled();
        }

        // If field is visible but not editable, disable it and add explanation
        if (!$fieldInfo['editable'] && !$fieldInfo['autoAssigned']) {
            $field->disabled();
            
            if ($fieldInfo['restrictionReason']) {
                $field->hint('Campo restringido')
                    ->hintIcon('heroicon-o-lock-closed')
                    ->hintColor('warning');
            }
        }

        // Add required indicator
        if ($fieldInfo['required']) {
            $field->required();
        }

        return $field;
    }

    /**
     * Create a placeholder for restricted fields
     */
    protected function createRestrictionPlaceholder(string $fieldName, array $fieldInfo): Placeholder
    {
        $label = $this->formatFieldLabel($fieldName);
        $reason = $fieldInfo['restrictionReason'] ?? 'Este campo no está disponible para tu rol.';

        return Placeholder::make($fieldName . '_restriction')
            ->label($label)
            ->content(function () use ($reason) {
                return view('filament.components.field-restriction', [
                    'reason' => $reason,
                ]);
            });
    }

    /**
     * Format field name into human-readable label
     */
    protected function formatFieldLabel(string $field): string
    {
        $labels = [
            'operator_id' => 'Operador',
            'mechanic_id' => 'Mecánico',
            'vehicle_id' => 'Vehículo',
            'vehicle_type' => 'Tipo de Vehículo',
            'maintenance_type' => 'Tipo de Mantenimiento',
            'expense_type' => 'Tipo de Gasto',
            'status' => 'Estado',
            'priority' => 'Prioridad',
            'used_by' => 'Usado por',
            'requested_by' => 'Solicitado por',
            'approved_by' => 'Aprobado por',
        ];

        return $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    /**
     * Check if a field should be visible
     */
    public function isFieldVisible(string $fieldName): bool
    {
        return $this->resolver->isFieldVisible($this->user, $this->modelClass, $fieldName);
    }

    /**
     * Check if a field should be editable
     */
    public function isFieldEditable(string $fieldName): bool
    {
        return $this->resolver->isFieldEditable($this->user, $this->modelClass, $fieldName);
    }

    /**
     * Get field information
     */
    public function getFieldInfo(string $fieldName): array
    {
        return $this->resolver->getFieldWithHelp($this->user, $this->modelClass, $fieldName);
    }
}
