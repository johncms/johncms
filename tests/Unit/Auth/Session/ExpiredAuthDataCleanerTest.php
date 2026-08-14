<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Session;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentAuthSessionRepository;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentPasswordResetTokenRepository;
use Johncms\Auth\Password\PasswordResetToken;
use Johncms\Auth\Password\PasswordResetTokens;
use Johncms\Auth\Schema\AuthSchema;
use Johncms\Auth\Session\AuthSession;
use Johncms\Auth\Session\AuthSessionManager;
use Johncms\Auth\Session\ExpiredAuthDataCleaner;
use Johncms\Auth\Session\SessionRevocationReason;
use Johncms\Auth\Session\SessionSettings;
use Johncms\Security\ClientInfoDTO;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;

final class ExpiredAuthDataCleanerTest extends TestCase
{
    use BootsInMemoryDatabase;

    private const DAY = 86400;

    private AuthSessionManager $sessions;

    private PasswordResetTokens $resetTokens;

    private ExpiredAuthDataCleaner $cleaner;

    protected function setUp(): void
    {
        $this->bootDatabase();
        AuthSchema::create(Capsule::schema());

        $sessionRepository = new EloquentAuthSessionRepository();
        $tokenRepository = new EloquentPasswordResetTokenRepository();

        $this->sessions = new AuthSessionManager($sessionRepository, new SessionSettings());
        $this->resetTokens = new PasswordResetTokens($tokenRepository);
        $this->cleaner = new ExpiredAuthDataCleaner($sessionRepository, $tokenRepository);
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testLiveSessionsAreLeftAlone(): void
    {
        $now = time();
        $this->sessions->start(7, true, $this->client(), now: $now);

        $removed = $this->cleaner->clean($now);

        self::assertSame(0, $removed['sessions']);
        self::assertSame(1, AuthSession::query()->count());
    }

    public function testExpiredSessionsGoOnceTheRetentionHasPassed(): void
    {
        $now = time();
        // Expired 31 days ago; the row is 61 days old.
        $this->sessions->start(7, true, $this->client(), now: $now - 61 * self::DAY);

        self::assertSame(1, $this->cleaner->clean($now)['sessions']);
        self::assertSame(0, AuthSession::query()->count());
    }

    /**
     * The profile shows recently closed devices, so a session is kept for a while after it
     * stops working rather than vanishing the moment it does.
     */
    public function testRecentlyExpiredSessionsAreKept(): void
    {
        $now = time();
        $this->sessions->start(7, false, $this->client(), now: $now - 2 * self::DAY);

        self::assertSame(0, $this->cleaner->clean($now)['sessions']);
    }

    /**
     * A session signed out today keeps a future expiry, so cleaning by expiry alone would never
     * reach it.
     */
    public function testSignedOutSessionsAreCleanedByTheirRevocationTime(): void
    {
        $now = time();
        $issued = $this->sessions->start(7, true, $this->client(), now: $now - 90 * self::DAY);
        $this->sessions->revoke($issued->session, SessionRevocationReason::Logout, $now - 60 * self::DAY);

        self::assertSame(1, $this->cleaner->clean($now)['sessions']);
    }

    public function testSignedOutSessionsAreKeptForTheRetentionPeriod(): void
    {
        $now = time();
        $issued = $this->sessions->start(7, true, $this->client(), now: $now);
        $this->sessions->revoke($issued->session, SessionRevocationReason::Logout, $now);

        self::assertSame(0, $this->cleaner->clean($now)['sessions']);
    }

    /**
     * Nothing displays a spent recovery link, so it goes as soon as it is dead.
     */
    public function testExpiredRecoveryLinksGoImmediately(): void
    {
        $now = time();
        $this->resetTokens->issue(7, $now - PasswordResetTokens::TTL - 1);
        $this->resetTokens->issue(8, $now);

        self::assertSame(1, $this->cleaner->clean($now)['reset_tokens']);
        self::assertSame(1, PasswordResetToken::query()->count());
    }

    private function client(): ClientInfoDTO
    {
        return new ClientInfoDTO('192.0.2.10', '', 'Mozilla/5.0');
    }
}
