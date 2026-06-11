<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Guestbook\Access;

use Johncms\Modules\Guestbook\Application\Access\GuestbookMode;
use Johncms\System\Http\Request;
use Johncms\System\Http\Session;
use PHPUnit\Framework\TestCase;
use Tests\Support\UserFactory;

final class GuestbookModeTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testAdminClubRequiresSessionFlagAndRights(): void
    {
        $mode = new GuestbookMode(UserFactory::make(rights: 1), new Session());

        self::assertFalse($mode->isAdminClub());
        self::assertTrue($mode->isGuestbook());

        $_SESSION['ga'] = 1;

        self::assertTrue($mode->isAdminClub());
        self::assertFalse($mode->isGuestbook());
    }

    public function testSessionFlagWithoutRightsIsIgnored(): void
    {
        $_SESSION['ga'] = 1;

        $mode = new GuestbookMode(UserFactory::make(rights: 0), new Session());

        self::assertFalse($mode->isAdminClub());
    }

    public function testGuestAccessListGrantsAdminClub(): void
    {
        $_SESSION['ga'] = 1;

        $user = UserFactory::make(rights: 0, attributes: ['id' => 5]);
        $mode = new GuestbookMode($user, new Session(), [5]);

        self::assertTrue($mode->isAdminClub());
    }

    public function testSwitchSetsAndRemovesSessionFlag(): void
    {
        $mode = new GuestbookMode(UserFactory::make(rights: 1), new Session());

        $mode->switch($this->makeRequest('set'));
        self::assertSame(1, $_SESSION['ga'] ?? null);

        $mode->switch($this->makeRequest('unset'));
        self::assertArrayNotHasKey('ga', $_SESSION);
    }

    public function testSwitchIsIgnoredWithoutAccess(): void
    {
        $mode = new GuestbookMode(UserFactory::make(rights: 0), new Session());

        $mode->switch($this->makeRequest('set'));

        self::assertArrayNotHasKey('ga', $_SESSION);
    }

    private function makeRequest(string $do): Request
    {
        $request = $this->createMock(Request::class);
        $request->method('getQuery')->with('do')->willReturn($do);

        return $request;
    }
}
