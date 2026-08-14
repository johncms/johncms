<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Session;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentAuthSessionRepository;
use Johncms\Auth\Schema\AuthSchema;
use Johncms\Auth\SecureToken;
use Johncms\Auth\Session\AuthSession;
use Johncms\Auth\Session\AuthSessionManager;
use Johncms\Auth\Session\SessionRevocationReason;
use Johncms\Auth\Session\SessionSettings;
use Johncms\Security\ClientInfoDTO;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;

final class AuthSessionManagerTest extends TestCase
{
    use BootsInMemoryDatabase;

    private const DAY = 86400;

    protected function setUp(): void
    {
        $this->bootDatabase();
        AuthSchema::create(Capsule::schema());
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testStartingASessionHandsOutASecretThatIdentifiesIt(): void
    {
        $manager = $this->manager();
        $now = time();

        $issued = $manager->start(7, true, $this->client(), now: $now);

        self::assertSame(7, $issued->session->user_id);
        self::assertNotNull($manager->find($issued->token, $now));
    }

    /**
     * The cookie is the only copy of the secret; the row must not be able to hand it back.
     */
    public function testOnlyTheDigestIsStored(): void
    {
        $issued = $this->manager()->start(7, true, $this->client());

        self::assertNotSame($issued->token, $issued->session->token_hash);
        self::assertSame(SecureToken::hash($issued->token), $issued->session->token_hash);
    }

    public function testAnUnknownSecretMatchesNothing(): void
    {
        $this->manager()->start(7, true, $this->client());

        self::assertNull($this->manager()->find('nonsense'));
        self::assertNull($this->manager()->find(''));
    }

    public function testRememberedSessionsLiveLongerThanTheOthers(): void
    {
        $manager = $this->manager();
        $now = time();

        $remembered = $manager->start(7, true, $this->client(), now: $now);
        $plain = $manager->start(8, false, $this->client(), now: $now);

        self::assertSame($now + 30 * self::DAY, $remembered->session->expires_at);
        self::assertSame($now + 12 * 3600, $plain->session->expires_at);
    }

    public function testAnExpiredSessionStopsAnswering(): void
    {
        $manager = $this->manager();
        $now = time();
        $issued = $manager->start(7, true, $this->client(), now: $now);

        self::assertNotNull($manager->find($issued->token, $now + 30 * self::DAY - 1));
        self::assertNull($manager->find($issued->token, $now + 30 * self::DAY + 1));
    }

    /**
     * The point of the sliding window: the clock runs from the last visit, not from signing in.
     */
    public function testAVisitPushesTheExpiryForward(): void
    {
        $manager = $this->manager();
        $now = time();
        $issued = $manager->start(7, true, $this->client(), now: $now);

        $fortnight = $now + 14 * self::DAY;
        $session = $manager->find($issued->token, $fortnight);
        self::assertNotNull($session);

        $expiresAt = $manager->touch($session, $fortnight);

        self::assertSame($fortnight + 30 * self::DAY, $expiresAt);
        // Day 44 from the sign-in, so the session outlives the original 30-day window.
        self::assertNotNull($manager->find($issued->token, $now + 40 * self::DAY));
    }

    public function testAVisitAfterTheWindowIsTooLate(): void
    {
        $manager = $this->manager();
        $now = time();
        $issued = $manager->start(7, true, $this->client(), now: $now);

        self::assertNull($manager->find($issued->token, $now + 31 * self::DAY));
    }

    /**
     * Extending is a write, so it must not happen on every request.
     */
    public function testExtendingIsThrottled(): void
    {
        $manager = $this->manager();
        $now = time();
        $issued = $manager->start(7, true, $this->client(), now: $now);

        self::assertNull($manager->touch($issued->session, $now + 60));
        self::assertNotNull($manager->touch($issued->session, $now + 301));
    }

    /**
     * The throttle is measured against the row, not against anything this process remembers —
     * otherwise a visitor returning after a fortnight would not be extended at all.
     */
    public function testAReturningVisitorIsExtendedImmediately(): void
    {
        $manager = $this->manager();
        $now = time();
        $issued = $manager->start(7, true, $this->client(), now: $now);

        $fortnight = $now + 14 * self::DAY;
        $reloaded = $manager->find($issued->token, $fortnight);
        self::assertNotNull($reloaded);

        self::assertNotNull($manager->touch($reloaded, $fortnight));
    }

    public function testRotationKeepsTheSessionAndKillsTheOldSecret(): void
    {
        $manager = $this->manager();
        $now = time();
        $issued = $manager->start(7, true, $this->client(), now: $now);

        $rotated = $manager->rotate($issued->session, $now + 10);

        self::assertSame($issued->session->id, $rotated->session->id);
        self::assertNotSame($issued->token, $rotated->token);
        self::assertNull($manager->find($issued->token, $now + 10));
        self::assertNotNull($manager->find($rotated->token, $now + 10));
    }

    /**
     * Rotation is deliberate and rare, so the throttle guarding the sliding extension must not
     * leave the row's activity stale.
     */
    public function testRotationRecordsTheVisitEvenInsideTheThrottleWindow(): void
    {
        $manager = $this->manager();
        $now = time();
        $issued = $manager->start(7, true, $this->client(), now: $now);

        $rotated = $manager->rotate($issued->session, $now + 30);

        self::assertSame($now + 30, $rotated->session->last_used_at);
    }

    public function testRevokedSessionsStopAnswering(): void
    {
        $manager = $this->manager();
        $issued = $manager->start(7, true, $this->client());

        $manager->revoke($issued->session, SessionRevocationReason::Logout);

        self::assertNull($manager->find($issued->token));
        self::assertSame('logout', $issued->session->revoked_reason);
    }

    public function testSigningOutEverywhereElseSparesTheCurrentSession(): void
    {
        $manager = $this->manager();
        $now = time();
        $here = $manager->start(7, true, $this->client(), now: $now);
        $phone = $manager->start(7, true, $this->client(), now: $now);
        $other = $manager->start(8, true, $this->client(), now: $now);

        $revoked = $manager->revokeAllFor(7, SessionRevocationReason::PasswordChange, $here->session->id, $now);

        self::assertSame(1, $revoked);
        self::assertNotNull($manager->find($here->token, $now));
        self::assertNull($manager->find($phone->token, $now));
        // Somebody else's sessions are untouched.
        self::assertNotNull($manager->find($other->token, $now));
    }

    public function testTheDeviceListSkipsImpersonationSessions(): void
    {
        $manager = $this->manager();
        $now = time();
        $manager->start(7, true, $this->client(), now: $now);
        $manager->start(7, true, $this->client(), impersonatorId: 9, now: $now);

        $active = $manager->activeFor(7, $now);

        self::assertCount(1, $active);
        self::assertNull($active->first()?->impersonator_id);
    }

    public function testImpersonationSessionsCanBeGivenTheirOwnShortLifetime(): void
    {
        $manager = $this->manager();
        $now = time();

        $issued = $manager->start(7, false, $this->client(), impersonatorId: 9, lifetimeOverride: 3600, now: $now);

        self::assertSame($now + 3600, $issued->session->expires_at);
        self::assertTrue($issued->session->is_impersonation);
    }

    public function testTheAbsoluteCapIsOffByDefault(): void
    {
        $issued = $this->manager()->start(7, true, $this->client());

        self::assertNull($issued->session->absolute_expires_at);
    }

    /**
     * When a site does turn the cap on, sliding must not push a session past it.
     */
    public function testTheAbsoluteCapIsNeverExtendedPast(): void
    {
        $manager = $this->manager(new SessionSettings(absoluteLifetime: 40 * self::DAY));
        $now = time();
        $issued = $manager->start(7, true, $this->client(), now: $now);

        $visit = $now + 20 * self::DAY;
        $session = $manager->find($issued->token, $visit);
        self::assertNotNull($session);

        self::assertSame($now + 40 * self::DAY, $manager->touch($session, $visit));
        self::assertNull($manager->find($issued->token, $now + 41 * self::DAY));
    }

    public function testExpiredRowsCanBeCleanedUp(): void
    {
        $manager = $this->manager();
        $now = time();
        $manager->start(7, false, $this->client(), now: $now - 13 * 3600);
        $manager->start(8, true, $this->client(), now: $now);

        $removed = (new EloquentAuthSessionRepository())->deleteDeadBefore($now);

        self::assertSame(1, $removed);
        self::assertSame(1, AuthSession::query()->count());
    }

    private function manager(?SessionSettings $settings = null): AuthSessionManager
    {
        return new AuthSessionManager(
            new EloquentAuthSessionRepository(),
            $settings ?? new SessionSettings()
        );
    }

    private function client(): ClientInfoDTO
    {
        return new ClientInfoDTO('192.0.2.10', '', 'Mozilla/5.0');
    }
}
