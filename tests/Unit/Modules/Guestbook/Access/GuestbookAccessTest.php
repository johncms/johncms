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

    public function testGuestCanWriteOnlyWhenGuestsAreAllowed(): void
    {
        $guest = UserFactory::make(valid: false);

        self::assertTrue((new GuestbookAccess($guest, new FakeAccessChecker(), ['mod_guest' => 2]))->canWrite());
        self::assertFalse((new GuestbookAccess($guest, new FakeAccessChecker(), ['mod_guest' => 1]))->canWrite());
    }

    public function testValidUserCanWrite(): void
    {
        $user = UserFactory::make();

        self::assertTrue((new GuestbookAccess($user, new FakeAccessChecker(), ['mod_guest' => 1]))->canWrite());
    }

    public function testBannedUserCannotWrite(): void
    {
        $config = ['mod_guest' => 1];

        self::assertFalse((new GuestbookAccess(UserFactory::make(banTypes: [1]), new FakeAccessChecker(), $config))->canWrite());
        self::assertFalse((new GuestbookAccess(UserFactory::make(banTypes: [13]), new FakeAccessChecker(), $config))->canWrite());
        self::assertTrue((new GuestbookAccess(UserFactory::make(banTypes: [3]), new FakeAccessChecker(), $config))->canWrite());
    }

    public function testCanClearAsksThePermission(): void
    {
        $config = ['mod_guest' => 1];
        $user = UserFactory::make();
        $allowed = new FakeAccessChecker([GuestbookPermissions::CLEAR]);

        self::assertTrue((new GuestbookAccess($user, $allowed, $config))->canClear());
        self::assertFalse((new GuestbookAccess($user, new FakeAccessChecker(), $config))->canClear());
    }

    public function testIsClosed(): void
    {
        $user = UserFactory::make();

        self::assertTrue((new GuestbookAccess($user, new FakeAccessChecker(), ['mod_guest' => 0]))->isClosed());
        self::assertFalse((new GuestbookAccess($user, new FakeAccessChecker(), ['mod_guest' => 1]))->isClosed());
        self::assertFalse((new GuestbookAccess($user, new FakeAccessChecker(), ['mod_guest' => 2]))->isClosed());
    }
}
