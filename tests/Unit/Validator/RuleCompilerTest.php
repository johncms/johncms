<?php

declare(strict_types=1);

namespace Tests\Unit\Validator;

use Johncms\Validator\RuleCompiler;
use Johncms\Validator\RuleConstraintFactoryInterface;
use Johncms\Validator\Rules\RequiresValueInterface;
use Johncms\Validator\Rules\RuleInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Sequentially;

final class RuleCompilerTest extends TestCase
{
    /**
     * The rules of a field run in order and stop at the first failure, the way the previous
     * engine did — otherwise one input starts showing an error per rule.
     */
    public function testTheRulesOfAFieldAreCompiledIntoASequence(): void
    {
        $compiled = $this->compile([new LengthRuleStub(min: 2, max: 5, allowEmpty: true)]);

        self::assertCount(1, $compiled->constraints);
        self::assertInstanceOf(Length::class, $compiled->constraints[0]);
    }

    public function testARuleRequiringAValueIsPrefixedWithNotBlank(): void
    {
        $compiled = $this->compile([new LengthRuleStub(min: 2, max: 5)]);

        self::assertCount(2, $compiled->constraints);
        self::assertInstanceOf(NotBlank::class, $compiled->constraints[0]);
        self::assertInstanceOf(Length::class, $compiled->constraints[1]);
    }

    /**
     * The opt-out of the null policy: an optional field says so explicitly, instead of the
     * requiredness being an accident of which rule it happens to carry.
     */
    public function testAnExplicitlyOptionalRuleIsNotPrefixed(): void
    {
        $compiled = $this->compile([new LengthRuleStub(min: 2, allowEmpty: true)]);

        self::assertCount(1, $compiled->constraints);
        self::assertInstanceOf(Length::class, $compiled->constraints[0]);
    }

    /**
     * Length has no `message` property at all — it says everything through minMessage and
     * maxMessage — so an override has to reach every message a constraint carries.
     */
    public function testTheMessageOfTheRuleReplacesTheMessagesOfTheConstraint(): void
    {
        $compiled = $this->compile(
            [new LengthRuleStub(min: 2, max: 5, allowEmpty: true, message: 'Custom message')]
        );

        $length = $compiled->constraints[0];
        self::assertInstanceOf(Length::class, $length);

        self::assertSame('Custom message', $length->minMessage);
        self::assertSame('Custom message', $length->maxMessage);
    }

    public function testTheMessageOfTheRuleAlsoReplacesTheOneOfTheNotBlankGuard(): void
    {
        $compiled = $this->compile([new LengthRuleStub(min: 2, message: 'Custom message')]);

        $notBlank = $compiled->constraints[0];
        self::assertInstanceOf(NotBlank::class, $notBlank);
        self::assertSame('Custom message', $notBlank->message);
    }

    /**
     * A rule of ours is its own constraint, so it needs no factory: it would have to be rewritten
     * for a different engine anyway, and a factory over it would isolate nothing.
     */
    public function testARuleThatIsAConstraintIsPassedThrough(): void
    {
        $rule = new ConstraintRuleStub();

        $compiled = $this->compile([$rule]);

        self::assertSame([$rule], $compiled->constraints);
    }

    public function testAFactoryMayExpandOneRuleIntoSeveralConstraints(): void
    {
        $compiled = $this->compile([new PairRuleStub()]);

        self::assertCount(2, $compiled->constraints);
    }

    public function testARuleWithoutAFactoryIsRejectedWithAnActionableMessage(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('johncms.validator.rule_factory');

        $this->compile([new UnregisteredRuleStub()]);
    }

    /**
     * @param list<RuleInterface> $rules
     */
    private function compile(array $rules): Sequentially
    {
        $compiled = (new RuleCompiler([new LengthRuleFactoryStub(), new PairRuleFactoryStub()]))->compile($rules);

        self::assertInstanceOf(Sequentially::class, $compiled);

        return $compiled;
    }
}

final readonly class LengthRuleStub implements RequiresValueInterface
{
    public function __construct(
        public ?int $min = null,
        public ?int $max = null,
        private bool $allowEmpty = false,
        private ?string $message = null,
    ) {
    }

    public function allowEmpty(): bool
    {
        return $this->allowEmpty;
    }

    public function message(): ?string
    {
        return $this->message;
    }
}

final class LengthRuleFactoryStub implements RuleConstraintFactoryInterface
{
    public static function ruleClass(): string
    {
        return LengthRuleStub::class;
    }

    public function create(RuleInterface $rule): Constraint
    {
        if (! $rule instanceof LengthRuleStub) {
            throw new RuntimeException('The factory was handed a rule it does not build.');
        }

        return new Length(min: $rule->min, max: $rule->max);
    }
}

final class ConstraintRuleStub extends Constraint implements RuleInterface
{
    public function message(): ?string
    {
        return null;
    }
}

final readonly class PairRuleStub implements RuleInterface
{
    public function message(): ?string
    {
        return null;
    }
}

final class PairRuleFactoryStub implements RuleConstraintFactoryInterface
{
    public static function ruleClass(): string
    {
        return PairRuleStub::class;
    }

    /**
     * @return list<Constraint>
     */
    public function create(RuleInterface $rule): array
    {
        return [new NotBlank(), new Length(min: 1)];
    }
}

final readonly class UnregisteredRuleStub implements RuleInterface
{
    public function message(): ?string
    {
        return null;
    }
}
