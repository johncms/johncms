<?php

declare(strict_types=1);

namespace Johncms\Validator\Rules;

/**
 * The value is an absolute http(s) address of a web page.
 *
 * Any other scheme is refused, `javascript:` and `data:` included: a value that passes is safe to
 * print into the href of a link.
 */
final readonly class WebAddress implements RequiresValueInterface
{
    public function __construct(
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
