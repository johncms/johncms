<?php

declare(strict_types=1);

namespace Johncms\Validator\Rules;

/**
 * The field must carry a value.
 *
 * A zero of any type counts as a value — '0', 0 and 0.0 all pass, matching both the mask the
 * previous engine used by default and Symfony's own NotBlank. A string of spaces does not: the
 * value is trimmed first.
 */
final readonly class NotEmpty implements RuleInterface
{
    public function __construct(private ?string $message = null)
    {
    }

    public function message(): ?string
    {
        return $this->message;
    }
}
