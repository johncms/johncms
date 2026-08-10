<?php

declare(strict_types=1);

namespace Tests\Unit\Validator;

use Johncms\Validator\ValidationResult;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ValidationResultTest extends TestCase
{
    public function testAResultWithoutErrorsIsValid(): void
    {
        $result = new ValidationResult();

        self::assertTrue($result->isValid());
        self::assertSame([], $result->getErrors());
    }

    public function testAResultWithErrorsIsInvalid(): void
    {
        $result = new ValidationResult(['name' => ['Value is required']]);

        self::assertFalse($result->isValid());
        self::assertTrue($result->hasError('name'));
        self::assertFalse($result->hasError('email'));
        self::assertSame('Value is required', $result->getFirstError('name'));
        self::assertNull($result->getFirstError('email'));
    }

    /**
     * The controllers add errors of their own after validation — a cycle in a section tree, a
     * name taken while the form was open. The result stays a result instead of being unpacked
     * into an array at the first such case.
     */
    public function testAnErrorCanBeAddedAfterValidation(): void
    {
        $result = (new ValidationResult())->withError('parent', 'A section cannot be its own parent');

        self::assertFalse($result->isValid());
        self::assertSame(['parent' => ['A section cannot be its own parent']], $result->getErrors());
    }

    public function testAddingAnErrorLeavesTheOriginalResultAlone(): void
    {
        $original = new ValidationResult(['name' => ['too short']]);

        $original->withError('name', 'also taken');

        self::assertSame(['name' => ['too short']], $original->getErrors());
    }

    public function testMergingCombinesTheMessagesOfTheSameField(): void
    {
        $result = (new ValidationResult(['name' => ['too short']]))
            ->merge(new ValidationResult(['name' => ['also taken'], 'email' => ['invalid']]));

        self::assertSame(
            ['name' => ['too short', 'also taken'], 'email' => ['invalid']],
            $result->getErrors()
        );
    }

    public function testThrowIfInvalidIsSilentForAValidResult(): void
    {
        (new ValidationResult())->throwIfInvalid(
            static fn (array $errors): RuntimeException => new RuntimeException('must not be thrown')
        );

        $this->expectNotToPerformAssertions();
    }

    public function testThrowIfInvalidHandsTheErrorsToTheFactory(): void
    {
        $result = new ValidationResult(['name' => ['too short']]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('name: too short');

        $result->throwIfInvalid(static function (array $errors): RuntimeException {
            $field = array_key_first($errors);

            return new RuntimeException($field . ': ' . $errors[$field][0]);
        });
    }
}
