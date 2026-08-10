<?php

declare(strict_types=1);

namespace Johncms\Validator\RuleValidators;

use Johncms\Http\Session;
use Johncms\Validator\Rules\Captcha;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class CaptchaValidator extends ConstraintValidator
{
    public function __construct(private readonly Session $session)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof Captcha) {
            throw new UnexpectedValueException($constraint, Captcha::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        $expected = $this->session->get($constraint->sessionField);

        if (is_string($expected) && $expected !== '' && strtolower($expected) === strtolower((string) $value)) {
            return;
        }

        $this->context->buildViolation($constraint->message)->addViolation();
    }
}
