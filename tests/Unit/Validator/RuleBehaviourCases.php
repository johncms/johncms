<?php

declare(strict_types=1);

namespace Tests\Unit\Validator;

/**
 * What every value-only rule answers for a given input, recorded from the current validator.
 *
 * The set is engine-agnostic on purpose: a case names a rule and its options, not a laminas
 * class, so the same provider can be run against the replacement. That is what makes it an
 * acceptance criterion for the migration rather than a test of the library being replaced.
 *
 * The rules that need a session, a user or the database are not here — their behaviour depends
 * on the environment rather than on the value, and LegacyValidatorCharacterizationTest covers
 * them one by one.
 */
final class RuleBehaviourCases
{
    /**
     * The values every rule is probed with: the empty-ish family, which is where laminas and
     * Symfony disagree the most (see the null policy of the migration plan).
     *
     * @return array<string, mixed>
     */
    public static function values(): array
    {
        return [
            'null'          => null,
            'empty string'  => '',
            'blank string'  => ' ',
            'string zero'   => '0',
            'int zero'      => 0,
            'float zero'    => 0.0,
            'false'         => false,
            'empty array'   => [],
            'string abc'    => 'abc',
        ];
    }

    /**
     * @return iterable<string, array{0: string, 1: array<string, mixed>, 2: mixed, 3: bool}>
     */
    public static function cases(): iterable
    {
        // rule => options => [value name => is valid]
        $matrix = [
            ['NotEmpty', [], [
                'null'         => false,
                'empty string' => false,
                'blank string' => false,
                // The default mask has no ZERO|INTEGER|FLOAT, so a zero of any type passes.
                'string zero'  => true,
                'int zero'     => true,
                'float zero'   => true,
                'false'        => false,
                'empty array'  => false,
                'string abc'   => true,
            ]],
            ['StringLength', ['min' => 2, 'max' => 5], [
                'null'         => false,
                'empty string' => false,
                'blank string' => false,
                'string zero'  => false,
                'int zero'     => false,
                'float zero'   => false,
                'false'        => false,
                'empty array'  => false,
                'string abc'   => true,
            ]],
            ['EmailAddress', [], [
                'null'         => false,
                'empty string' => false,
                'blank string' => false,
                'string zero'  => false,
                'int zero'     => false,
                'float zero'   => false,
                'false'        => false,
                'empty array'  => false,
                'string abc'   => false,
            ]],
            ['InArray', ['haystack' => ['a', 'b']], [
                'null'         => false,
                'empty string' => false,
                'blank string' => false,
                'string zero'  => false,
                'int zero'     => false,
                'float zero'   => false,
                'false'        => false,
                'empty array'  => false,
                'string abc'   => false,
            ]],
            ['Between', ['min' => 1, 'max' => 10], [
                'null'         => false,
                'empty string' => false,
                'blank string' => false,
                'string zero'  => false,
                'int zero'     => false,
                'float zero'   => false,
                'false'        => false,
                'empty array'  => false,
                'string abc'   => false,
            ]],
            ['Identical', ['token' => 'abc'], [
                'null'         => false,
                'empty string' => false,
                'blank string' => false,
                'string zero'  => false,
                'int zero'     => false,
                'float zero'   => false,
                'false'        => false,
                'empty array'  => false,
                'string abc'   => true,
            ]],
        ];

        foreach ($matrix as [$rule, $options, $expectations]) {
            foreach (self::values() as $valueName => $value) {
                yield sprintf('%s: %s', self::label($rule, $options), $valueName)
                    => [$rule, $options, $value, $expectations[$valueName]];
            }
        }

        foreach (self::specificCases() as $name => $case) {
            yield $name => $case;
        }
    }

    /**
     * The cases that carry a decision rather than a shape: where a rule accepts, where it stops,
     * and how strictly it compares. These are the ones a replacement engine breaks silently.
     *
     * @return iterable<string, array{0: string, 1: array<string, mixed>, 2: mixed, 3: bool}>
     */
    private static function specificCases(): iterable
    {
        yield 'StringLength: at the lower bound' => ['StringLength', ['min' => 2, 'max' => 5], 'ab', true];
        yield 'StringLength: at the upper bound' => ['StringLength', ['min' => 2, 'max' => 5], 'abcde', true];
        yield 'StringLength: above the upper bound' => ['StringLength', ['min' => 2, 'max' => 5], 'abcdef', false];
        // Whitespace counts towards the length: the value reaching a rule is already trimmed by
        // TrimStringsMiddleware, so this only shows up for data assembled in code.
        yield 'StringLength: padding counts' => ['StringLength', ['min' => 2, 'max' => 5], '  ab  ', false];
        // A number is not a string: the rule rejects the type rather than measuring it.
        yield 'StringLength: an integer is rejected as a type' => ['StringLength', ['min' => 2], 12345, false];

        yield 'EmailAddress: a plain address' => ['EmailAddress', [], 'user@example.com', true];
        yield 'EmailAddress: a hostname without a dot' => ['EmailAddress', [], 'user@localhost', false];

        yield 'InArray: a member of the haystack' => ['InArray', ['haystack' => ['a', 'b']], 'a', true];
        yield 'InArray: an integer member' => ['InArray', ['haystack' => [1, 2]], 1, true];
        // Loose comparison: '1' matches the integer 1 and 1 matches the string '1'. Symfony's
        // Choice always compares strictly, so this is the case that breaks on the replacement.
        yield 'InArray: a numeric string against integers' => ['InArray', ['haystack' => [1, 2]], '1', true];
        yield 'InArray: an integer against numeric strings' => ['InArray', ['haystack' => ['1', '2']], 1, true];
        yield 'InArray: a leading-numeric string does not match' => ['InArray', ['haystack' => [1, 2]], '1abc', false];

        yield 'Between: inside the range' => ['Between', ['min' => 1, 'max' => 10], 5, true];
        yield 'Between: a numeric string inside the range' => ['Between', ['min' => 1, 'max' => 10], '5', true];
        yield 'Between: the bounds are inclusive' => ['Between', ['min' => 1, 'max' => 10], 1, true];
        yield 'Between: above the range' => ['Between', ['min' => 1, 'max' => 10], 11, false];

        // Identical compares strictly, unlike InArray.
        yield 'Identical: a different case does not match' => ['Identical', ['token' => 'abc'], 'ABC', false];
        yield 'Identical: an integer does not match its string' => ['Identical', ['token' => '1'], 1, false];
    }

    /**
     * @param array<string, mixed> $options
     */
    private static function label(string $rule, array $options): string
    {
        if ($options === []) {
            return $rule;
        }

        $parts = [];
        foreach ($options as $key => $value) {
            $parts[] = $key . '=' . (is_array($value) ? '[' . implode(',', $value) . ']' : $value);
        }

        return $rule . '(' . implode(' ', $parts) . ')';
    }
}
