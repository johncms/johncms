<?php

declare(strict_types=1);

namespace Johncms\Validator\RuleFactories;

use Johncms\Validator\RuleConstraintFactoryInterface;
use Johncms\Validator\Rules\Between;
use Johncms\Validator\Rules\RuleInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Range;

final readonly class BetweenRuleFactory implements RuleConstraintFactoryInterface
{
    public static function ruleClass(): string
    {
        return Between::class;
    }

    public function create(RuleInterface $rule): Constraint
    {
        if (! $rule instanceof Between) {
            throw new \InvalidArgumentException('The factory was handed a rule it does not build.');
        }

        // Range refuses minMessage together with maxMessage when both bounds are set, so the one
        // message the rule has says the whole range — which is what the msgid already does.
        return new Range(
            notInRangeMessage: d__('system', 'The input is not between \'%min%\' and \'%max%\', inclusively'),
            min: $rule->min,
            max: $rule->max,
        );
    }
}
