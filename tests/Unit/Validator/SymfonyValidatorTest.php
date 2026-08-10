<?php

declare(strict_types=1);

namespace Tests\Unit\Validator;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Validator\RuleCompiler;
use Johncms\Validator\SymfonyValidator;
use Johncms\Validator\Translation\GettextTranslator;
use Johncms\Validator\ValidationResult;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

/**
 * The engine is real here rather than mocked: what is worth testing is how the whole array is
 * validated and how the violations become the error array the rest of the application reads.
 */
final class SymfonyValidatorTest extends TestCase
{
    protected function setUp(): void
    {
        TranslatorFunctions::register(new Translator());
    }

    public function testAValidPayloadProducesAValidResult(): void
    {
        $result = $this->validator()->validate(
            ['name' => 'John'],
            ['name' => [new LengthRuleStub(min: 2, max: 5)]]
        );

        self::assertTrue($result->isValid());
        self::assertSame([], $result->getErrors());
    }

    /**
     * The shape the flash session, ValidationException, the domain exceptions of the modules and
     * the field-errors component all read: field name => list of messages. A Collection reports
     * the path as "[name]", and that must not leak into the keys.
     */
    public function testErrorsAreKeyedByTheBareFieldName(): void
    {
        $result = $this->validator()->validate(
            ['name' => ''],
            ['name' => [new LengthRuleStub(min: 2, max: 5)]]
        );

        self::assertFalse($result->isValid());
        self::assertSame(['name'], array_keys($result->getErrors()));
        self::assertContainsOnly('string', $result->getErrors()['name']);
    }

    public function testAFieldStopsAtItsFirstFailingRule(): void
    {
        $result = $this->validator()->validate(
            ['name' => ''],
            ['name' => [new LengthRuleStub(min: 10, max: 20)]]
        );

        self::assertCount(1, $result->getErrors()['name']);
    }

    /**
     * A field the form declares rules for but did not submit is validated as an empty value, not
     * reported as a missing key of the collection.
     */
    public function testAFieldMissingFromTheDataIsValidatedAsEmpty(): void
    {
        $result = $this->validator()->validate([], ['name' => [new LengthRuleStub(min: 2)]]);

        self::assertFalse($result->isValid());
        self::assertArrayHasKey('name', $result->getErrors());
    }

    /**
     * The data carries more than the form validates — the whole request body, as a rule — and
     * the extra keys are none of the validator's business.
     */
    public function testDataWithoutRulesIsLeftAlone(): void
    {
        $result = $this->validator()->validate(
            ['name' => 'John', 'nickname' => ''],
            ['name' => [new LengthRuleStub(min: 2)]]
        );

        self::assertTrue($result->isValid());
    }

    /**
     * Errors that belong to the form rather than to a field — an antiflood delay, a ban — have a
     * key of their own instead of hanging on whichever field was available.
     */
    public function testFormLevelRulesReportUnderTheReservedKey(): void
    {
        $result = $this->validator()->validate(
            [],
            [ValidationResult::FORM_KEY => [new LengthRuleStub(min: 1)]]
        );

        self::assertTrue($result->hasError(ValidationResult::FORM_KEY));
    }

    private function validator(): SymfonyValidator
    {
        $engine = Validation::createValidatorBuilder()
            ->setTranslator(new GettextTranslator())
            ->getValidator();

        return new SymfonyValidator($engine, new RuleCompiler([new LengthRuleFactoryStub()]));
    }
}
