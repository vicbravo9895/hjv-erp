<?php

namespace App\Filament\Forms\Components;

use App\Contracts\FormFieldResolverInterface;
use App\Models\User;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\Facades\Auth;

class FieldRestrictionPlaceholder extends Placeholder
{
    protected string $modelClass;
    protected string $restrictedField;

    public static function make(string $name): static
    {
        $static = parent::make($name);
        
        return $static
            ->content(fn() => $static->getRestrictionContent());
    }

    /**
     * Set the model class for field resolution
     */
    public function forModel(string $modelClass): static
    {
        $this->modelClass = $modelClass;
        return $this;
    }

    /**
     * Set the restricted field name
     */
    public function forField(string $field): static
    {
        $this->restrictedField = $field;
        return $this;
    }

    /**
     * Get the restriction content with styling
     */
    protected function getRestrictionContent(): string
    {
        if (!isset($this->modelClass) || !isset($this->restrictedField)) {
            return '';
        }

        $user = Auth::user();
        if (!$user instanceof User) {
            return '';
        }

        $resolver = app(FormFieldResolverInterface::class);
        $fieldInfo = $resolver->getFieldWithHelp($user, $this->modelClass, $this->restrictedField);

        if (!$fieldInfo['restrictionReason']) {
            return '';
        }

        $icon = '🔒';
        $reason = $fieldInfo['restrictionReason'];
        
        return <<<HTML
            <div style="
                background-color: #FEF3C7;
                border-left: 4px solid #F59E0B;
                padding: 12px 16px;
                border-radius: 6px;
                margin: 8px 0;
            ">
                <div style="
                    display: flex;
                    align-items: start;
                    gap: 8px;
                ">
                    <span style="font-size: 20px;">{$icon}</span>
                    <div>
                        <p style="
                            margin: 0;
                            color: #92400E;
                            font-size: 14px;
                            line-height: 1.5;
                        ">{$reason}</p>
                    </div>
                </div>
            </div>
        HTML;
    }
}
