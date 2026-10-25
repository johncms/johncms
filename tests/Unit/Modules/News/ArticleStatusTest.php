<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\News;

use DateTimeImmutable;
use Johncms\Modules\News\Domain\Enums\ArticleStatus;
use PHPUnit\Framework\TestCase;

final class ArticleStatusTest extends TestCase
{
    public function testTheSwitchAndTheDatesDecide(): void
    {
        $now = new DateTimeImmutable('2026-10-25 12:00:00');
        $past = new DateTimeImmutable('2026-10-01 00:00:00');
        $future = new DateTimeImmutable('2026-11-01 00:00:00');

        self::assertSame(ArticleStatus::PUBLISHED, ArticleStatus::of(true, null, null, $now));
        self::assertSame(ArticleStatus::PUBLISHED, ArticleStatus::of(true, $past, $future, $now));
        self::assertSame(ArticleStatus::SCHEDULED, ArticleStatus::of(true, $future, null, $now));
        self::assertSame(ArticleStatus::EXPIRED, ArticleStatus::of(true, null, $past, $now));
        self::assertSame(ArticleStatus::DRAFT, ArticleStatus::of(false, $past, $future, $now));
    }

    public function testAnEndThatHasPassedWinsOverAStartToCome(): void
    {
        $now = new DateTimeImmutable('2026-10-25 12:00:00');

        self::assertSame(
            ArticleStatus::EXPIRED,
            ArticleStatus::of(true, new DateTimeImmutable('2026-11-01'), new DateTimeImmutable('2026-10-01'), $now)
        );
    }
}
