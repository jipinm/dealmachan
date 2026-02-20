<?php
/**
 * Simple input validator.
 *
 * Usage:
 *   $v = new Validator($_POST);
 *   $v->required('email')->email('email')->required('password')->minLength('password', 8);
 *   if ($v->fails()) Response::validationError($v->errors());
 *   $data = $v->validated();
 */
class Validator {

    private array $data;
    private array $errors = [];
    private array $validated = [];

    public function __construct(array $data) {
        $this->data = $data;
    }

    // ── Rules ────────────────────────────────────────────────────────────────

    public function required(string $field, string $label = ''): self {
        $label = $label ?: $field;
        $value = $this->data[$field] ?? null;
        if ($value === null || $value === '') {
            $this->errors[$field][] = "{$label} is required.";
        } else {
            $this->validated[$field] = $value;
        }
        return $this;
    }

    public function optional(string $field): self {
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            $this->validated[$field] = $this->data[$field];
        }
        return $this;
    }

    public function email(string $field, string $label = ''): self {
        $label = $label ?: $field;
        $value = $this->data[$field] ?? null;
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "{$label} must be a valid email address.";
        }
        return $this;
    }

    public function minLength(string $field, int $min, string $label = ''): self {
        $label = $label ?: $field;
        $value = $this->data[$field] ?? '';
        if (strlen((string)$value) < $min) {
            $this->errors[$field][] = "{$label} must be at least {$min} characters.";
        }
        return $this;
    }

    public function maxLength(string $field, int $max, string $label = ''): self {
        $label = $label ?: $field;
        $value = $this->data[$field] ?? '';
        if (strlen((string)$value) > $max) {
            $this->errors[$field][] = "{$label} must not exceed {$max} characters.";
        }
        return $this;
    }

    public function in(string $field, array $allowed, string $label = ''): self {
        $label = $label ?: $field;
        $value = $this->data[$field] ?? null;
        if ($value !== null && $value !== '' && !in_array($value, $allowed, true)) {
            $this->errors[$field][] = "{$label} must be one of: " . implode(', ', $allowed) . '.';
        }
        return $this;
    }

    public function numeric(string $field, string $label = ''): self {
        $label = $label ?: $field;
        $value = $this->data[$field] ?? null;
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->errors[$field][] = "{$label} must be a number.";
        }
        return $this;
    }

    public function confirmed(string $field, string $confirmation = '', string $label = ''): self {
        $label        = $label ?: $field;
        $confirmation = $confirmation ?: $field . '_confirmation';
        if (($this->data[$field] ?? '') !== ($this->data[$confirmation] ?? '')) {
            $this->errors[$field][] = "{$label} confirmation does not match.";
        }
        return $this;
    }

    // ── Result ───────────────────────────────────────────────────────────────

    public function fails(): bool {
        return !empty($this->errors);
    }

    public function errors(): array {
        return $this->errors;
    }

    /** Returns only the fields that passed validation rules */
    public function validated(): array {
        return $this->validated;
    }

    /** Get a single (possibly raw) input value */
    public function input(string $field, mixed $default = null): mixed {
        return $this->data[$field] ?? $default;
    }
}
