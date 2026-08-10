<?php

declare(strict_types=1);

namespace Johncms\Validator\RuleFactories;

use Johncms\Validator\RuleConstraintFactoryInterface;
use Johncms\Validator\Rules\RuleInterface;
use Johncms\Validator\Rules\StringLength;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Type;

final readonly class StringLengthRuleFactory implements RuleConstraintFactoryInterface
{
    public static function ruleClass(): string
    {
        return StringLength::class;
    }

    /**
     * @return list<Constraint>
     */
    public function create(RuleInterface $rule): array
    {
        if (! $rule instanceof StringLength) {
            throw new \InvalidArgumentException('The factory was handed a rule it does not build.');
        }

        return [
            // Length measures the decimal notation of a number happily; the previous engine
            // rejected the type instead, and a field declared as text stays text.
            new Type(type: 'string', message: d__('system', 'Invalid type given. String expected')),
            new Length(
                min: $rule->min,
                max: $rule->max,
                minMessage: d__('system', 'The input is less than %min% characters long'),
                maxMessage: d__('system', 'The input is more than %max% characters long'),
            ),
        ];
    }
}
