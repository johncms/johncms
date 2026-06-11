<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Guestbook\Access;

use Johncms\Config\ConfigRepository;
use Johncms\Modules\Guestbook\Application\Access\GuestbookAccess;
use PHPUnit\Framework\TestCase;
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

        self::assertTrue((new GuestbookAccess($guest, ['mod_guest' => 2]))->canWrite());
        self::assertFalse((new GuestbookAccess($guest, ['mod_guest' => 1]))->canWrite());
    }

    public function testValidUserCanWrite(): void
    {
        $user = UserFactory::make();

        self::assertTrue((new GuestbookAccess($user, ['mod_guest' => 1]))->canWrite());
    }

    public function testBannedUserCannotWrite(): void
    {
        $config = ['mod_guest' => 1];

        self::assertFalse((new GuestbookAccess(UserFactory::make(banTypes: [1]), $config))->canWrite());
        self::assertFalse((new GuestbookAccess(UserFactory::make(banTypes: [13]), $config))->canWrite());
        self::assertTrue((new GuestbookAccess(UserFactory::make(banTypes: [3]), $config))->canWrite());
    }

    public function testCanClearRequiresAdminRights(): void
    {
        $config = ['mod_guest' => 1];

        self::assertTrue((new GuestbookAccess(UserFactory::make(rights: 7), $config))->canClear());
        self::assertTrue((new GuestbookAccess(UserFactory::make(rights: 9), $config))->canClear());
        self::assertFalse((new GuestbookAccess(UserFactory::make(rights: 6), $config))->canClear());
    }

    public function testIsClosed(): void
    {
        $user = UserFactory::make();

        self::assertTrue((new GuestbookAccess($user, ['mod_guest' => 0]))->isClosed());
        self::assertFalse((new GuestbookAccess($user, ['mod_guest' => 1]))->isClosed());
        self::assertFalse((new GuestbookAccess($user, ['mod_guest' => 2]))->isClosed());
    }
}
