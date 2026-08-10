<?php

declare(strict_types=1);

namespace Johncms\Validator\RuleFactories;

use Johncms\Validator\RuleConstraintFactoryInterface;
use Johncms\Validator\Rules\EmailAddress;
use Johncms\Validator\Rules\MxRecord;
use Johncms\Validator\Rules\RuleInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Email;

final readonly class EmailAddressRuleFactory implements RuleConstraintFactoryInterface
{
    public static function ruleClass(): string
    {
        return EmailAddress::class;
    }

    /**
     * @return list<Constraint>
     */
    public function create(RuleInterface $rule): array
    {
        if (! $rule instanceof EmailAddress) {
            throw new \InvalidArgumentException('The factory was handed a rule it does not build.');
        }

        $constraints = [
            new Email(
                message: d__('system', 'The input is not a valid email address. Use the basic format local-part@hostname'),
            ),
        ];

        if ($rule->checkMxRecord) {
            $constraints[] = new MxRecord();
        }

        return $constraints;
    }
}
