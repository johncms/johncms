<?php

declare(strict_types=1);

namespace Johncms\Validator;

use Johncms\Validator\Rules\RuleInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Turns one rule value object into the constraints that enforce it.
 *
 * A module registers its own factory in its config/services.php with the
 * johncms.validator.rule_factory tag and the compiler picks it up — adding a rule never means
 * touching the core. The previous validator allowed the same through addRule(), and losing that
 * would have been a regression.
 *
 * Only the rules wrapping a built-in constraint need a factory. A rule of our own implements
 * Constraint itself and the compiler passes it through untouched.
 */
interface RuleConstraintFactoryInterface
{
    /** @return class-string<RuleInterface> */
    public static function ruleClass(): string;

    /**
     * @return Constraint|list<Constraint>
     */
    public function create(RuleInterface $rule): Constraint|array;
}
