<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Authorization;

use Johncms\Auth\Authorization\PermissionMatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PermissionMatcherTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function patterns(): iterable
    {
        yield 'exact key' => ['forum.topic.delete', 'forum.topic.delete', true];
        yield 'different key' => ['forum.topic.delete', 'forum.topic.edit', false];
        yield 'global wildcard' => ['*', 'forum.topic.delete', true];
        yield 'module wildcard' => ['forum.*', 'forum.topic.delete', true];
        yield 'nested wildcard' => ['forum.topic.*', 'forum.topic.delete', true];
        yield 'wildcard of another module' => ['news.*', 'forum.topic.delete', false];
        yield 'wildcard does not cover its own bare key' => ['forum.*', 'forum', false];
        // A star inside a segment would let a typo grant more than the pattern names.
        yield 'partial segment is not a wildcard' => ['forum.top*', 'forum.topic.delete', false];
        yield 'prefix without a star grants nothing below it' => ['forum', 'forum.topic.delete', false];
    }

    #[DataProvider('patterns')]
    public function testMatches(string $pattern, string $permission, bool $expected): void
    {
        self::assertSame($expected, PermissionMatcher::matches($pattern, $permission));
    }

    public function testMatchesAnyNeedsOneHit(): void
    {
        self::assertTrue(PermissionMatcher::matchesAny(['news.*', 'forum.topic.delete'], 'forum.topic.delete'));
        self::assertFalse(PermissionMatcher::matchesAny(['news.*', 'library.*'], 'forum.topic.delete'));
        self::assertFalse(PermissionMatcher::matchesAny([], 'forum.topic.delete'));
    }
}
