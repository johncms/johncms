<?php

declare(strict_types=1);

namespace Johncms\Validator;

use Johncms\Validator\Translation\GettextTranslator;
use Psr\Container\ContainerInterface;
use Symfony\Component\Validator\ContainerConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface as SymfonyValidatorInterface;

/**
 * Builds the engine behind SymfonyValidator.
 *
 * Two things it wires that the container cannot do on its own: the messages go through the
 * gettext catalogs of the project (see GettextTranslator), and the validators of our own rules
 * are resolved from the container, so they take their dependencies through a constructor instead
 * of reaching for a service locator inside isValid(), the way the previous rules did.
 */
final readonly class SymfonyValidatorFactory
{
    public function __construct(
        private ContainerInterface $container,
        private GettextTranslator $translator,
    ) {
    }

    public function __invoke(): SymfonyValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->setTranslator($this->translator)
            ->setConstraintValidatorFactory(new ContainerConstraintValidatorFactory($this->container))
            ->getValidator();
    }
}
