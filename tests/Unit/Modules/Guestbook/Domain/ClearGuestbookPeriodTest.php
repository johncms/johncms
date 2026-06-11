<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Guestbook\Domain;

use Johncms\Modules\Guestbook\Domain\Enums\ClearGuestbookPeriod;
use PHPUnit\Framework\TestCase;

final class ClearGuestbookPeriodTest extends TestCase
{
    public function testMaxAgeMapsPeriodsToSeconds(): void
    {
        self::assertSame(604800, ClearGuestbookPeriod::OlderThanWeek->maxAge());
        self::assertSame(86400, ClearGuestbookPeriod::OlderThanDay->maxAge());
    }

    public function testMaxAgeIsNullForAll(): void
    {
        self::assertNull(ClearGuestbookPeriod::All->maxAge());
    }

    public function testBackedValues(): void
    {
        self::assertSame(ClearGuestbookPeriod::OlderThanWeek, ClearGuestbookPeriod::from(0));
        self::assertSame(ClearGuestbookPeriod::OlderThanDay, ClearGuestbookPeriod::from(1));
        self::assertSame(ClearGuestbookPeriod::All, ClearGuestbookPeriod::from(2));
    }
}
