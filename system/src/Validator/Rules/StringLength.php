<?php

declare(strict_types=1);

namespace Johncms\Validator\Rules;

/**
 * The value is a string of the given length.
 *
 * The type is part of the rule: a number is rejected rather than measured, the way the previous
 * engine did it. Symfony's Length happily measures the decimal notation of an integer, which
 * would let a field declared as text through on a numeric value.
 */
final readonly class StringLength implements RequiresValueInterface
{
    public function __construct(
        public ?int $min = null,
        public ?int $max = null,
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
