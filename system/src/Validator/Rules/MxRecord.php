<?php

declare(strict_types=1);

namespace Johncms\Validator\Rules;

use Johncms\Validator\RuleValidators\MxRecordValidator;
use Symfony\Component\Validator\Constraint;

/**
 * The host of the email address has an MX or an A record.
 *
 * Symfony has no DNS check at all, and dropping the one the previous engine did on registration,
 * profile editing and the installer would have quietly weakened the spam defence of the site.
 * So it is a rule of the project — with three live callers, unlike the rules that were left
 * behind for having none.
 */
final class MxRecord extends Constraint implements RuleInterface
{
    public string $message = '\'%hostname%\' does not appear to have any valid MX or A records for the email address';

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
        return MxRecordValidator::class;
    }
}
