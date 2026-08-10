<?php

declare(strict_types=1);

namespace Johncms\Validator\RuleFactories;

use Johncms\Validator\RuleConstraintFactoryInterface;
use Johncms\Validator\Rules\Identical;
use Johncms\Validator\Rules\RuleInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\IdenticalTo;

final readonly class IdenticalRuleFactory implements RuleConstraintFactoryInterface
{
    public static function ruleClass(): string
    {
        return Identical::class;
    }

    public function create(RuleInterface $rule): Constraint
    {
        if (! $rule instanceof Identical) {
            throw new \InvalidArgumentException('The factory was handed a rule it does not build.');
        }

        return new IdenticalTo(
            value: $rule->token,
            message: d__('system', 'The two given tokens do not match'),
        );
    }
}
