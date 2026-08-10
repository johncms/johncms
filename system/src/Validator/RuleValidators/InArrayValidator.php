<?php

declare(strict_types=1);

namespace Johncms\Validator\RuleValidators;

use Johncms\Validator\Rules\InArray;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class InArrayValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof InArray) {
            throw new UnexpectedValueException($constraint, InArray::class);
        }

        // The empty value is the business of the NotBlank the compiler puts in front of the rule,
        // matching how the built-in validators of Symfony treat it.
        if ($value === null || $value === '') {
            return;
        }

        if (in_array($value, $constraint->haystack, $constraint->strict)) {
            return;
        }

        $this->context->buildViolation($constraint->message)->addViolation();
    }
}
