<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Domain;

use DateTimeImmutable;
use Johncms\Modules\Collections\Domain\Enums\ItemStatus;
use PHPUnit\Framework\TestCase;

final class ItemStatusTest extends TestCase
{
    public function testTheStatusComesFromTheSwitchAndTheWindow(): void
    {
        $now = new DateTimeImmutable('2026-10-30 12:00');
        $before = new DateTimeImmutable('2026-10-01');
        $after = new DateTimeImmutable('2026-11-01');

        self::assertSame(ItemStatus::PUBLISHED, ItemStatus::of(true, null, null, $now));
        self::assertSame(ItemStatus::PUBLISHED, ItemStatus::of(true, $before, $after, $now));
        self::assertSame(ItemStatus::HIDDEN, ItemStatus::of(false, $before, $after, $now));
        self::assertSame(ItemStatus::SCHEDULED, ItemStatus::of(true, $after, null, $now));
        self::assertSame(ItemStatus::EXPIRED, ItemStatus::of(true, null, $before, $now));
    }
}
