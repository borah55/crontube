<?php
declare(strict_types=1);

namespace App\Core;

final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @var array<string, mixed> */
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string $field, string $message = 'is required'): self
    {
        $v = trim((string)($this->data[$field] ?? ''));
        if ($v === '') {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function length(string $field, int $min, int $max): self
    {
        $v = (string)($this->data[$field] ?? '');
        $len = mb_strlen($v);
        if ($len < $min || $len > $max) {
            $this->errors[$field] = "must be between $min and $max characters";
        }
        return $this;
    }

    public function regex(string $field, string $pattern, string $message): self
    {
        $v = (string)($this->data[$field] ?? '');
        if (!preg_match($pattern, $v)) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function email(string $field): self
    {
        $v = (string)($this->data[$field] ?? '');
        if (!filter_var($v, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'must be a valid email';
        }
        return $this;
    }

    public function matches(string $field, string $other, string $message = 'must match'): self
    {
        if (($this->data[$field] ?? null) !== ($this->data[$other] ?? null)) {
            $this->errors[$field] = $message;
        }
        return $this;
    }

    public function numeric(string $field, float $min = -INF, float $max = INF): self
    {
        $v = $this->data[$field] ?? null;
        if (!is_numeric($v) || (float)$v < $min || (float)$v > $max) {
            $this->errors[$field] = 'must be numeric';
        }
        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return $this->errors ? reset($this->errors) : null;
    }
}
