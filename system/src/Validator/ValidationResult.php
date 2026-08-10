<?php

declare(strict_types=1);

namespace Johncms\Validator;

/**
 * The outcome of one validation run.
 *
 * The error array keeps the shape every consumer already reads — field name => list of messages
 * — because it travels far beyond the validator: into the flash session, into ValidationException,
 * into the domain exceptions of the modules and into the field-errors component of the theme.
 *
 * Errors that belong to the form as a whole rather than to a field (an antiflood delay, a ban)
 * live under the FORM_KEY.
 */
final readonly class ValidationResult
{
    /** The field name reserved for the errors of the form itself. */
    public const FORM_KEY = '_form';

    /**
     * @param array<string, list<string>> $errors
     */
    public function __construct(private array $errors = [])
    {
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    /**
     * @return array<string, list<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    public function getFirstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * A domain error discovered after validation — a cycle in a section tree, a name taken while
     * the form was open. Without this the result would be unpacked into a plain array at the
     * first such case and lose its type for the rest of the controller.
     */
    public function withError(string $field, string $message): self
    {
        $errors = $this->errors;
        $errors[$field][] = $message;

        return new self($errors);
    }

    public function merge(self $other): self
    {
        $errors = $this->errors;
        foreach ($other->errors as $field => $messages) {
            $errors[$field] = [...($errors[$field] ?? []), ...$messages];
        }

        return new self($errors);
    }

    /**
     * @param callable(array<string, list<string>>): \Throwable $exceptionFactory
     */
    public function throwIfInvalid(callable $exceptionFactory): void
    {
        if (! $this->isValid()) {
            throw $exceptionFactory($this->errors);
        }
    }
}
