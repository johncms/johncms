<?php

declare(strict_types=1);

namespace Johncms\Validator\Rules;

use Johncms\Validator\RuleValidators\InArrayValidator;
use Symfony\Component\Validator\Constraint;

/**
 * The value is one of the allowed ones.
 *
 * A rule of ours rather than a wrapper over Symfony's Choice, because Choice compares strictly
 * and the previous engine did not: today a select posting "1" matches a haystack of integer ids,
 * and a straight swap would have started rejecting exactly those forms. The strict comparison is
 * available, but it has to be asked for.
 */
final class InArray extends Constraint implements RequiresValueInterface
{
    public string $message = 'The input was not found in the haystack';

    private readonly ?string $ruleMessage;

    /**
     * @param list<mixed> $haystack
     */
    public function __construct(
        public array $haystack = [],
        public bool $strict = false,
        private readonly bool $allowEmpty = false,
        ?string $message = null,
    ) {
        parent::__construct([]);

        $this->ruleMessage = $message;

        if ($message !== null) {
            $this->message = $message;
        }
    }

    public function allowEmpty(): bool
    {
        return $this->allowEmpty;
    }

    public function message(): ?string
    {
        return $this->ruleMessage;
    }

    public function validatedBy(): string
    {
        return InArrayValidator::class;
    }
}
