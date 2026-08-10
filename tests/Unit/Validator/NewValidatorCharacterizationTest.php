<?php

declare(strict_types=1);

namespace Tests\Unit\Validator;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Validator\RuleCompiler;
use Johncms\Validator\RuleFactories\BetweenRuleFactory;
use Johncms\Validator\RuleFactories\EmailAddressRuleFactory;
use Johncms\Validator\RuleFactories\IdenticalRuleFactory;
use Johncms\Validator\RuleFactories\NotEmptyRuleFactory;
use Johncms\Validator\RuleFactories\StringLengthRuleFactory;
use Johncms\Validator\Rules\Between;
use Johncms\Validator\Rules\EmailAddress;
use Johncms\Validator\Rules\Identical;
use Johncms\Validator\Rules\InArray;
use Johncms\Validator\Rules\NotEmpty;
use Johncms\Validator\Rules\RuleInterface;
use Johncms\Validator\Rules\StringLength;
use Johncms\Validator\SymfonyValidator;
use Johncms\Validator\Translation\GettextTranslator;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

/**
 * The acceptance criterion of the migration: the new implementation answers every case of
 * RuleBehaviourCases exactly as the previous engine did.
 *
 * The provider names a rule and its options rather than a class, so the same expectations that
 * were recorded from laminas in LegacyValidatorCharacterizationTest are checked here against
 * symfony/validator. A verdict that changes has to change this file, which is what keeps a
 * regression from passing as a refactoring.
 */
final class NewValidatorCharacterizationTest extends TestCase
{
    protected function setUp(): void
    {
        TranslatorFunctions::register(new Translator());
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProviderExternal(RuleBehaviourCases::class, 'cases')]
    public function testRuleVerdict(string $rule, array $options, mixed $value, bool $expected): void
    {
        $result = $this->validator()->validate(['field' => $value], ['field' => [$this->rule($rule, $options)]]);

        self::assertSame($expected, $result->isValid(), implode(' ', $result->getErrors()['field'] ?? []));
    }

    /**
     * @param array<string, mixed> $options
     */
    private function rule(string $rule, array $options): RuleInterface
    {
        return match ($rule) {
            'NotEmpty' => new NotEmpty(),
            'StringLength' => new StringLength(min: $options['min'] ?? null, max: $options['max'] ?? null),
            'EmailAddress' => new EmailAddress(),
            'InArray' => new InArray(haystack: $options['haystack']),
            'Between' => new Between(min: $options['min'], max: $options['max']),
            'Identical' => new Identical(token: $options['token']),
            default => self::fail('The case names a rule the mapping does not know: ' . $rule),
        };
    }

    private function validator(): SymfonyValidator
    {
        $engine = Validation::createValidatorBuilder()
            ->setTranslator(new GettextTranslator())
            ->getValidator();

        return new SymfonyValidator($engine, new RuleCompiler([
            new NotEmptyRuleFactory(),
            new StringLengthRuleFactory(),
            new EmailAddressRuleFactory(),
            new BetweenRuleFactory(),
            new IdenticalRuleFactory(),
        ]));
    }
}
