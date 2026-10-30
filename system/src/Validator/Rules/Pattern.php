<?php

declare(strict_types=1);

namespace Johncms\Validator\Rules;

/**
 * The value matches a regular expression.
 *
 * The default message only says the format is wrong, so a field with a format of its own passes
 * a message that names that format.
 */
final readonly class Pattern implements RequiresValueInterface
{
    public function __construct(
        public string $pattern,
        private bool $allowEmpty = false,
        private ?string $message = null,
    ) {
    }

    public function allowEmpty(): bool
    {
        return $this->allowEmpty;
    }

    public function message(): ?string
    {
        return $this->message;
    }
}
