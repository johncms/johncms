<?php

declare(strict_types=1);

namespace Johncms\Validator\RuleValidators;

use Johncms\Validator\Rules\MxRecord;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class MxRecordValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof MxRecord) {
            throw new UnexpectedValueException($constraint, MxRecord::class);
        }

        if (! is_string($value) || $value === '') {
            return;
        }

        $host = substr(strrchr($value, '@') ?: '', 1);

        if ($host === '') {
            // Not an address at all: the Email constraint in front of this one says so, and
            // repeating it here would put two messages under one field.
            return;
        }

        // A records count as well as MX ones: a domain accepting mail on its own host is
        // deliverable, and the previous engine accepted it too.
        if (checkdnsrr($host, 'MX') || checkdnsrr($host, 'A')) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('%hostname%', $host)
            ->addViolation();
    }
}
