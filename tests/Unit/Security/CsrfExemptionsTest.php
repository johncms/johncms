<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use Johncms\Security\CsrfExemptions;
use PHPUnit\Framework\TestCase;

final class CsrfExemptionsTest extends TestCase
{
    public function testAnEmptyListExemptsNothing(): void
    {
        self::assertFalse((new CsrfExemptions())->exempts('/guestbook'));
    }

    public function testAnExactPathIsExempt(): void
    {
        $exemptions = new CsrfExemptions(['/legacy/endpoint']);

        self::assertTrue($exemptions->exempts('/legacy/endpoint'));
        self::assertFalse($exemptions->exempts('/legacy/endpoint/nested'));
        self::assertFalse($exemptions->exempts('/legacy'));
    }

    public function testAWildcardCoversThePathsBelowIt(): void
    {
        $exemptions = new CsrfExemptions(['/api/*']);

        self::assertTrue($exemptions->exempts('/api/items'));
        self::assertTrue($exemptions->exempts('/api/items/42'));
        self::assertFalse($exemptions->exempts('/api'));
        self::assertFalse($exemptions->exempts('/guestbook'));
    }

    public function testTheConfiguredPatternsAreReadable(): void
    {
        self::assertSame(['/api/*'], (new CsrfExemptions(['/api/*']))->all());
    }
}
