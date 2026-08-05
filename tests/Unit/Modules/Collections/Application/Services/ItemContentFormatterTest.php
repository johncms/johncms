<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\Services;

use HTMLPurifier;
use Johncms\Modules\Collections\Application\Services\ItemContentFormatter;
use PHPUnit\Framework\TestCase;

final class ItemContentFormatterTest extends TestCase
{
    private function formatter(): ItemContentFormatter
    {
        return new ItemContentFormatter(new HTMLPurifier());
    }

    public function testReturnsNullForNullOrEmpty(): void
    {
        self::assertNull($this->formatter()->format(null));
        self::assertNull($this->formatter()->format(''));
    }

    public function testStripsDangerousMarkupButKeepsSafeTags(): void
    {
        // Markup by contract, so the assertions read it as the string it prints as.
        $result = (string) $this->formatter()->format('<p>Hello <b>world</b></p><script>alert(1)</script>');

        self::assertStringContainsString('<b>world</b>', $result);
        self::assertStringNotContainsString('<script', $result);
        self::assertStringNotContainsString('alert(1)', $result);
    }

    public function testRemovesEventHandlerAttributes(): void
    {
        $result = (string) $this->formatter()->format('<a href="https://example.test" onclick="evil()">link</a>');

        self::assertStringContainsString('href="https://example.test"', $result);
        self::assertStringNotContainsString('onclick', $result);
    }
}
