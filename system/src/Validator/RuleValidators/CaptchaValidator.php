<?php

declare(strict_types=1);

namespace Johncms\Validator\RuleValidators;

use Johncms\Captcha\CaptchaManager;
use Johncms\Validator\Rules\Captcha;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class CaptchaValidator extends ConstraintValidator
{
    public function __construct(
        private readonly CaptchaManager $captcha,
        // The address of the visitor, when there is one. Read off the stack rather than through
        // Environment: that one insists on a request being served, and a rule must not blow up
        // when a form is validated outside an HTTP cycle.
        private readonly RequestStack $requestStack,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof Captcha) {
            throw new UnexpectedValueException($constraint, Captcha::class);
        }

        // Requiredness is the business of the NotBlank the compiler puts in front of the rule.
        if ($value === null || $value === '') {
            return;
        }

        $result = $this->captcha->verify(
            (string) $value,
            $constraint->scope,
            // The remote services take the address of the visitor into account; the built-in
            // provider ignores it.
            $this->requestStack->getMainRequest()?->getClientIp(),
        );

        if ($result->passed) {
            return;
        }

        // A message the form asked for wins; otherwise the provider's own reason is shown, so a
        // service that could not be reached does not read as a mistake of the visitor.
        $message = $constraint->message() ?? $result->failure?->message() ?? $constraint->message;

        $this->context->buildViolation($message)->addViolation();
    }
}
