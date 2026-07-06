<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Domain\Enums;

use Johncms\Modules\Collections\Domain\Enums\FieldType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FieldTypeTest extends TestCase
{
    /**
     * @return iterable<string, array{FieldType, string}>
     */
    public static function valueColumnProvider(): iterable
    {
        yield 'string'   => [FieldType::String_, 'value_string'];
        yield 'select'   => [FieldType::Select, 'value_string'];
        yield 'file'     => [FieldType::File, 'value_string'];
        yield 'text'     => [FieldType::Text, 'value_text'];
        yield 'html'     => [FieldType::Html, 'value_text'];
        yield 'integer'  => [FieldType::Integer, 'value_int'];
        yield 'boolean'  => [FieldType::Boolean, 'value_int'];
        yield 'relation' => [FieldType::Relation, 'value_int'];
        yield 'double'   => [FieldType::Double, 'value_double'];
        yield 'date'     => [FieldType::Date, 'value_date'];
        yield 'datetime' => [FieldType::Datetime, 'value_date'];
    }

    #[DataProvider('valueColumnProvider')]
    public function testValueColumnMapping(FieldType $type, string $expected): void
    {
        self::assertSame($expected, $type->valueColumn());
    }

    public function testEveryCaseMapsToAKnownValueColumn(): void
    {
        $allowed = ['value_string', 'value_text', 'value_int', 'value_double', 'value_date'];
        foreach (FieldType::cases() as $type) {
            self::assertContains($type->valueColumn(), $allowed);
        }
    }

    public function testCastReturnsNullForNull(): void
    {
        self::assertNull(FieldType::Integer->cast(null));
        self::assertNull(FieldType::String_->cast(null));
    }

    public function testCastToScalarTypes(): void
    {
        self::assertSame(42, FieldType::Integer->cast('42'));
        self::assertSame(7, FieldType::Relation->cast('7'));
        self::assertSame(1.5, FieldType::Double->cast('1.5'));
        self::assertTrue(FieldType::Boolean->cast('1'));
        self::assertFalse(FieldType::Boolean->cast('0'));
        self::assertSame('hello', FieldType::String_->cast('hello'));
        self::assertSame('2026-07-04 10:00:00', FieldType::Datetime->cast('2026-07-04 10:00:00'));
    }
}
