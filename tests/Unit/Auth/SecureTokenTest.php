<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use Johncms\Auth\SecureToken;
use PHPUnit\Framework\TestCase;

final class SecureTokenTest extends TestCase
{
    /**
     * These values travel in links, cookie values and Authorization headers, so anything that
     * would need escaping on the way must not appear in them.
     */
    public function testTokensAreUrlSafe(): void
    {
        for ($i = 0; $i < 50; $i++) {
            self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', SecureToken::generate());
        }
    }

    public function testTokensAreLongEnoughToBeUnguessable(): void
    {
        // 32 random bytes encoded without padding.
        self::assertSame(43, strlen(SecureToken::generate()));
        self::assertSame(22, strlen(SecureToken::generate(16)));
    }

    public function testTokensDoNotRepeat(): void
    {
        $tokens = [];

        for ($i = 0; $i < 100; $i++) {
            $tokens[] = SecureToken::generate();
        }

        self::assertCount(100, array_unique($tokens));
    }

    public function testHashingIsStableAndHidesTheToken(): void
    {
        $token = SecureToken::generate();

        self::assertSame(SecureToken::hash($token), SecureToken::hash($token));
        self::assertNotSame($token, SecureToken::hash($token));
        self::assertSame(64, strlen(SecureToken::hash($token)));
    }
}
