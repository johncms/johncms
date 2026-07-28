<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Guestbook\Access;

use Johncms\Http\Session;
use Johncms\Modules\Guestbook\Application\Access\GuestbookMode;
use Johncms\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Tests\Support\UserFactory;

final class GuestbookModeTest extends TestCase
{
    private Session $session;

    protected function setUp(): void
    {
        $this->session = new Session(new MockArraySessionStorage());
    }

    public function testAdminClubRequiresSessionFlagAndRights(): void
    {
        $mode = new GuestbookMode(UserFactory::make(rights: 1), $this->session);

        self::assertFalse($mode->isAdminClub());
        self::assertTrue($mode->isGuestbook());

        $this->session->set('ga', 1);

        self::assertTrue($mode->isAdminClub());
        self::assertFalse($mode->isGuestbook());
    }

    public function testSessionFlagWithoutRightsIsIgnored(): void
    {
        $this->session->set('ga', 1);

        $mode = new GuestbookMode(UserFactory::make(rights: 0), $this->session);

        self::assertFalse($mode->isAdminClub());
    }

    public function testGuestAccessListGrantsAdminClub(): void
    {
        $this->session->set('ga', 1);

        $user = UserFactory::make(rights: 0, attributes: ['id' => 5]);
        $mode = new GuestbookMode($user, $this->session, [5]);

        self::assertTrue($mode->isAdminClub());
    }

    public function testSwitchSetsAndRemovesSessionFlag(): void
    {
        $mode = new GuestbookMode(UserFactory::make(rights: 1), $this->session);

        $mode->switch($this->makeRequest('set'));
        self::assertTrue($this->session->has('ga'));

        $mode->switch($this->makeRequest('unset'));
        self::assertFalse($this->session->has('ga'));
    }

    public function testSwitchIsIgnoredWithoutAccess(): void
    {
        $mode = new GuestbookMode(UserFactory::make(rights: 0), $this->session);

        $mode->switch($this->makeRequest('set'));

        self::assertFalse($this->session->has('ga'));
    }

    private function makeRequest(string $do): Request
    {
        return new Request(['do' => $do]);
    }
}
