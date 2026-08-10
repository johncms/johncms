<?php

declare(strict_types=1);

namespace Johncms\Validator\Rules;

use Johncms\Validator\RuleValidators\FloodValidator;
use Symfony\Component\Validator\Constraint;

/**
 * The visitor is not posting too often.
 *
 * A statement about the visitor rather than about a value, so it belongs to the form and not to
 * a field: it goes under ValidationResult::FORM_KEY, where the previous rulesets hung it on the
 * csrf_token field for want of anywhere better.
 */
final class Flood extends Constraint implements RuleInterface
{
    public string $message = 'You cannot add the message so often. Please, wait %value% seconds.';

    private readonly ?string $ruleMessage;

    public function __construct(?string $message = null)
    {
        parent::__construct([]);

        $this->ruleMessage = $message;

        if ($message !== null) {
            $this->message = $message;
        }
    }

    public function message(): ?string
    {
        return $this->ruleMessage;
    }

    public function validatedBy(): string
    {
        return FloodValidator::class;
    }
}
