<?php

declare(strict_types=1);

namespace Johncms\Validator;

use Johncms\Validator\Rules\RequiresValueInterface;
use Johncms\Validator\Rules\RuleInterface;
use RuntimeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Sequentially;

/**
 * Turns the rules of a field into the constraint the engine runs.
 *
 * Two behaviours are deliberate and are what keeps the replacement invisible to the call sites:
 *
 * 1. The rules of a field are wrapped in Sequentially, so the field stops at the first failure
 *    and reports one message. Symfony checks every constraint of a field by default, which would
 *    start showing three errors under one input.
 * 2. A rule that requires a value is prefixed with NotBlank (see RequiresValueInterface).
 */
final readonly class RuleCompiler
{
    /**
     * The message properties a constraint may carry. A rule's own message replaces every one of
     * them that the constraint actually has: Length, for one, has no `message` at all and says
     * everything through minMessage and maxMessage.
     */
    private const MESSAGE_PROPERTIES = ['message', 'minMessage', 'maxMessage', 'exactMessage', 'notInRangeMessage'];

    /** @var array<class-string<RuleInterface>, RuleConstraintFactoryInterface> */
    private array $factories;

    /**
     * @param iterable<RuleConstraintFactoryInterface> $factories
     */
    public function __construct(iterable $factories)
    {
        $map = [];
        foreach ($factories as $factory) {
            $map[$factory::ruleClass()] = $factory;
        }

        $this->factories = $map;
    }

    /**
     * @param list<RuleInterface> $rules
     */
    public function compile(array $rules): Constraint
    {
        $constraints = [];

        foreach ($rules as $rule) {
            foreach ($this->constraintsFor($rule) as $constraint) {
                $constraints[] = $constraint;
            }
        }

        return new Sequentially($constraints);
    }

    /**
     * @return list<Constraint>
     */
    private function constraintsFor(RuleInterface $rule): array
    {
        $constraints = [];

        if ($rule instanceof RequiresValueInterface && ! $rule->allowEmpty()) {
            // Trimming here as well as in TrimStringsMiddleware: the middleware only reaches the
            // request bags, while a form may hand the validator data assembled in code.
            $constraints[] = $this->withMessage(new NotBlank(normalizer: 'trim'), $rule->message());
        }

        foreach ($this->ruleConstraints($rule) as $constraint) {
            $constraints[] = $this->withMessage($constraint, $rule->message());
        }

        return $constraints;
    }

    /**
     * @return list<Constraint>
     */
    private function ruleConstraints(RuleInterface $rule): array
    {
        // A rule of ours is its own constraint: there is nothing to isolate from the vendor when
        // the rule would have to be rewritten for a different engine anyway.
        if ($rule instanceof Constraint) {
            return [$rule];
        }

        $factory = $this->factories[$rule::class] ?? throw new RuntimeException(
            sprintf(
                'No constraint factory is registered for the rule %s. A module registers one with'
                . ' the johncms.validator.rule_factory tag.',
                $rule::class
            )
        );

        $constraints = $factory->create($rule);

        return is_array($constraints) ? array_values($constraints) : [$constraints];
    }

    private function withMessage(Constraint $constraint, ?string $message): Constraint
    {
        if ($message === null) {
            return $constraint;
        }

        foreach (self::MESSAGE_PROPERTIES as $property) {
            if (property_exists($constraint, $property)) {
                $constraint->{$property} = $message;
            }
        }

        return $constraint;
    }
}
