<?php

declare(strict_types=1);

namespace Tests\Unit\Users;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\UserFactory;

/**
 * The website of a profile is an address a visitor typed about themselves, and it ends up in an
 * href. Nothing but http(s) may become a link, and the address is printed escaped either way.
 */
final class UserWebsiteAttributeTest extends TestCase
{
    private function website(string $stored): mixed
    {
        return UserFactory::make(attributes: ['www' => $stored])->website;
    }

    public function testNoAddressIsNull(): void
    {
        // Null rather than empty markup: markup is an object, and an object is always truthy.
        self::assertNull($this->website(''));
        self::assertNull($this->website('   '));
    }

    public function testAnHttpAddressBecomesALink(): void
    {
        $result = (string) $this->website('https://example.test/page');

        self::assertSame(
            '<a href="https://example.test/page" rel="nofollow noopener">https://example.test/page</a>',
            $result
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function addressesThatMustNotBecomeALink(): array
    {
        return [
            'javascript'        => ['javascript:alert(document.cookie)'],
            'javascript upcase' => ['JaVaScRiPt:alert(1)'],
            'vbscript'          => ['vbscript:msgbox(1)'],
            'data uri'          => ['data:text/html;base64,PHNjcmlwdD4='],
            'file'              => ['file:///etc/passwd'],
            'no scheme'         => ['example.test'],
            'plain words'       => ['my site, ask me'],
        ];
    }

    #[DataProvider('addressesThatMustNotBecomeALink')]
    public function testOnlyWebSchemesBecomeALink(string $stored): void
    {
        $result = (string) $this->website($stored);

        self::assertStringNotContainsString('<a', $result);
        self::assertStringNotContainsString('href', $result);
    }

    public function testMarkupInTheFieldIsPrintedAsText(): void
    {
        // The field is not markup, so a tag in it is shown, not rendered.
        $result = (string) $this->website('<script>alert(1)</script>');

        self::assertStringNotContainsString('<script', $result);
        self::assertStringContainsString('&lt;script&gt;', $result);
    }

    public function testAnAddressThatTriesToBreakOutOfTheHrefStaysText(): void
    {
        $result = (string) $this->website('https://example.test/?a=1&b=2"><img src=x onerror=alert(1)>');

        // Not a valid URL, so it never becomes a link, and every angle bracket and quote of it
        // is escaped: the payload is visible as text and nothing more.
        self::assertStringNotContainsString('<a ', $result);
        self::assertStringNotContainsString('<img', $result);
        self::assertStringContainsString('&lt;img', $result);
        self::assertStringContainsString('&quot;', $result);
    }

    public function testAnAddressStoredEscapedIsNotEscapedTwice(): void
    {
        // The profile form escapes on input, so what is stored may already carry entities.
        $result = (string) $this->website('https://example.test/?a=1&amp;b=2');

        self::assertStringContainsString('href="https://example.test/?a=1&amp;b=2"', $result);
        self::assertStringNotContainsString('&amp;amp;', $result);
    }
}
