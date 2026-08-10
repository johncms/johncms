<?php

declare(strict_types=1);

namespace Johncms\Validator\Rules;

/**
 * The value lies within the range, bounds included.
 */
final readonly class Between implements RequiresValueInterface
{
    public function __construct(
        public int|float|string $min,
        public int|float|string $max,
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
