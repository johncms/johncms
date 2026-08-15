<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Guestbook\Access;

use Johncms\Config\ConfigRepository;
use Johncms\Modules\Guestbook\Application\Access\GuestbookAccess;
use Johncms\Modules\Guestbook\Application\Services\GuestbookPermissions;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeAccessChecker;
use Tests\Support\UserFactory;

final class GuestbookAccessTest extends TestCase
{
    protected function setUp(): void
    {
        ConfigRepository::init([]);
    }

    public function testReadingAndWritingAskTheirOwnPermissions(): void
    {
        $user = UserFactory::make();

        $reader = new GuestbookAccess($user, new FakeAccessChecker([GuestbookPermissions::VIEW]));
        self::assertTrue($reader->canRead());
        self::assertFalse($reader->canWrite());

        $writer = new GuestbookAccess($user, new FakeAccessChecker([GuestbookPermissions::POST]));
        self::assertTrue($writer->canWrite());
        self::assertFalse($writer->canRead());
    }

    /**
     * A ban of the guestbook or of the whole site keeps the entry form away even from somebody
     * whose role allows writing.
     */
    public function testABannedVisitorCannotWrite(): void
    {
        $allowed = new FakeAccessChecker([GuestbookPermissions::POST]);

        self::assertFalse((new GuestbookAccess(UserFactory::make(banTypes: [1]), $allowed))->canWrite());
        self::assertFalse((new GuestbookAccess(UserFactory::make(banTypes: [13]), $allowed))->canWrite());
        self::assertTrue((new GuestbookAccess(UserFactory::make(banTypes: [3]), $allowed))->canWrite());
    }

    public function testCanClearAsksThePermission(): void
    {
        $user = UserFactory::make();
        $allowed = new FakeAccessChecker([GuestbookPermissions::CLEAR]);

        self::assertTrue((new GuestbookAccess($user, $allowed))->canClear());
        self::assertFalse((new GuestbookAccess($user, new FakeAccessChecker()))->canClear());
    }
}
