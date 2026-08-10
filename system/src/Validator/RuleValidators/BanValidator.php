<?php

declare(strict_types=1);

namespace Johncms\Validator\RuleValidators;

use Johncms\Users\User;
use Johncms\Validator\Rules\Ban;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class BanValidator extends ConstraintValidator
{
    public function __construct(private readonly User $user)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof Ban) {
            throw new UnexpectedValueException($constraint, Ban::class);
        }

        foreach ($constraint->bans as $ban) {
            if (array_key_exists($ban, $this->user->ban)) {
                $this->context->buildViolation($constraint->message)->addViolation();

                return;
            }
        }
    }
}
