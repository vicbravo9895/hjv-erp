<?php

namespace App\Services\Validation;

class ValidationResult
{
    public function __construct(
        public bool $isValid,
        public array $errors = [],
        public array $warnings = [],
        public array $suggestions = []
    ) {}

    public static function success(): self
    {
        return new self(true);
    }

    public static function failure(array $errors): self
    {
        return new self(false, $errors);
    }

    public function addError(string $error): self
    {
        $this->errors[] = $error;
        $this->isValid = false;
        return $this;
    }

    public function addWarning(string $warning): self
    {
        $this->warnings[] = $warning;
        return $this;
    }

    public function addSuggestion(string $suggestion): self
    {
        $this->suggestions[] = $suggestion;
        return $this;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    public function hasSuggestions(): bool
    {
        return !empty($this->suggestions);
    }

    public function getFormattedMessage(): string
    {
        $message = '';

        if ($this->hasErrors()) {
            $message .= '❌ ' . implode("\n❌ ", $this->errors);
        }

        if ($this->hasWarnings()) {
            if ($message) {
                $message .= "\n\n";
            }
            $message .= '⚠️ ' . implode("\n⚠️ ", $this->warnings);
        }

        if ($this->hasSuggestions()) {
            if ($message) {
                $message .= "\n\n";
            }
            $message .= '💡 ' . implode("\n💡 ", $this->suggestions);
        }

        return $message;
    }

    public function toArray(): array
    {
        return [
            'isValid' => $this->isValid,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'suggestions' => $this->suggestions,
        ];
    }
}
