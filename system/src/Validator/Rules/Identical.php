<?php

declare(strict_types=1);

namespace Johncms\Validator\Rules;

/**
 * The value equals the token, compared strictly.
 *
 * Used for the consent checkboxes of the registration and contact forms, and for the honeypot
 * field — which is expected to stay empty, and therefore declares allowEmpty.
 */
final readonly class Identical implements RequiresValueInterface
{
    public function __construct(
        public mixed $token,
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
