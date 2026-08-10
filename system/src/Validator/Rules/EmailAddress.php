<?php

declare(strict_types=1);

namespace Johncms\Validator\Rules;

/**
 * The value is an email address.
 *
 * The host is checked for a DNS record only when asked: the previous engine offered the same as
 * useMxCheck, and registration, profile editing and the installer all switch it on. Symfony has
 * no DNS check of its own, so that part is the MxRecord rule of this project.
 */
final readonly class EmailAddress implements RequiresValueInterface
{
    public function __construct(
        public bool $checkMxRecord = false,
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
