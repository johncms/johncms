<?php

declare(strict_types=1);

namespace Johncms\Validator\RuleValidators;

use Johncms\Security\AntifloodCheckerInterface;
use Johncms\Validator\Rules\Flood;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * The dependency arrives through the constructor: the rule this replaces reached for the service
 * locator inside isValid(), which is what made it untestable without booting the container.
 */
final class FloodValidator extends ConstraintValidator
{
    public function __construct(private readonly AntifloodCheckerInterface $antifloodChecker)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof Flood) {
            throw new UnexpectedValueException($constraint, Flood::class);
        }

        $remainingSeconds = $this->antifloodChecker->getRemainingSeconds();

        if ($remainingSeconds <= 0) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('%value%', (string) $remainingSeconds)
            ->addViolation();
    }
}
