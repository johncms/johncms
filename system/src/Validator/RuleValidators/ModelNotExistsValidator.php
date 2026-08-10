<?php

declare(strict_types=1);

namespace Johncms\Validator\RuleValidators;

use Johncms\Validator\Rules\ModelNotExists;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class ModelNotExistsValidator extends ConstraintValidator
{
    use ChecksModelRows;

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof ModelNotExists) {
            throw new UnexpectedValueException($constraint, ModelNotExists::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        if (! $this->rowExists($constraint->model, $constraint->field, $value, $constraint->exclude)) {
            return;
        }

        $this->context->buildViolation($constraint->message)->addViolation();
    }
}
