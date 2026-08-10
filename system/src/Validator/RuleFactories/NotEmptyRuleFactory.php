<?php

declare(strict_types=1);

namespace Johncms\Validator\RuleFactories;

use Johncms\Validator\RuleConstraintFactoryInterface;
use Johncms\Validator\Rules\NotEmpty;
use Johncms\Validator\Rules\RuleInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;

final readonly class NotEmptyRuleFactory implements RuleConstraintFactoryInterface
{
    public static function ruleClass(): string
    {
        return NotEmpty::class;
    }

    public function create(RuleInterface $rule): Constraint
    {
        // Trimmed first, so a string of spaces is empty — the previous engine said the same.
        return new NotBlank(
            message: d__('system', 'Value is required and can\'t be empty'),
            normalizer: 'trim',
        );
    }
}
