<?php

declare(strict_types=1);

namespace Tests\Unit\Validator;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Validator\RuleCompiler;
use Johncms\Validator\RuleFactories\PatternRuleFactory;
use Johncms\Validator\RuleFactories\WebAddressRuleFactory;
use Johncms\Validator\Rules\Pattern;
use Johncms\Validator\Rules\RuleInterface;
use Johncms\Validator\Rules\WebAddress;
use Johncms\Validator\SymfonyValidator;
use Johncms\Validator\Translation\GettextTranslator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class WebAddressAndPatternRulesTest extends TestCase
{
    protected function setUp(): void
    {
        TranslatorFunctions::register(new Translator());
    }

    /**
     * @return iterable<string, array{0: mixed, 1: bool}>
     */
    public static function addresses(): iterable
    {
        yield 'https' => ['https://example.com/page?a=1', true];
        yield 'http' => ['http://example.com', true];
        yield 'a scheme alone' => ['http://', false];
        yield 'no top-level domain' => ['https://localhost', false];
        yield 'javascript' => ['javascript:alert(1)', false];
        yield 'data' => ['data:text/html,<b>x</b>', false];
        yield 'ftp' => ['ftp://example.com', false];
        yield 'protocol-relative' => ['//example.com', false];
        yield 'empty' => ['', false];
        yield 'not a string' => [123, false];
    }

    #[DataProvider('addresses')]
    public function testWebAddressAcceptsOnlyHttpAddresses(mixed $value, bool $expected): void
    {
        self::assertSame($expected, $this->isValid($value, new WebAddress()));
    }

    public function testAnOptionalWebAddressMayBeEmpty(): void
    {
        self::assertTrue($this->isValid('', new WebAddress(allowEmpty: true)));
    }

    public function testPatternMatchesTheExpression(): void
    {
        $rule = new Pattern('/^[\da-f]{6}$/i');

        self::assertTrue($this->isValid('D9480f', $rule));
        self::assertFalse($this->isValid('ABC_EF', $rule));
        self::assertFalse($this->isValid('', $rule));
        self::assertTrue($this->isValid('', new Pattern('/^[\da-f]{6}$/i', allowEmpty: true)));
    }

    public function testPatternTakesTheMessageOfTheField(): void
    {
        $result = $this->validator()->validate(
            ['color' => 'red'],
            ['color' => [new Pattern('/^[\da-f]{6}$/i', message: 'Six hexadecimal digits')]]
        );

        self::assertSame(['color' => ['Six hexadecimal digits']], $result->getErrors());
    }

    private function isValid(mixed $value, RuleInterface $rule): bool
    {
        return $this->validator()->validate(['field' => $value], ['field' => [$rule]])->isValid();
    }

    private function validator(): SymfonyValidator
    {
        $engine = Validation::createValidatorBuilder()
            ->setTranslator(new GettextTranslator())
            ->getValidator();

        return new SymfonyValidator($engine, new RuleCompiler([
            new WebAddressRuleFactory(),
            new PatternRuleFactory(),
        ]));
    }
}
