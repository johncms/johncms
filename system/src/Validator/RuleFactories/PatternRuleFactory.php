<?php

declare(strict_types=1);

namespace Johncms\Validator\RuleFactories;

use Johncms\Validator\RuleConstraintFactoryInterface;
use Johncms\Validator\Rules\Pattern;
use Johncms\Validator\Rules\RuleInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Type;

final readonly class PatternRuleFactory implements RuleConstraintFactoryInterface
{
    public static function ruleClass(): string
    {
        return Pattern::class;
    }

    /**
     * @return list<Constraint>
     */
    public function create(RuleInterface $rule): array
    {
        if (! $rule instanceof Pattern) {
            throw new \InvalidArgumentException('The factory was handed a rule it does not build.');
        }

        return [
            new Type(type: 'string', message: d__('system', 'Invalid type given. String expected')),
            new Regex(pattern: $rule->pattern, message: d__('system', 'The input does not match the expected format')),
        ];
    }
}
