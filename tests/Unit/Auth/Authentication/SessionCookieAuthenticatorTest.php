<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Authentication;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Auth\Authentication\SessionCookieAuthenticator;
use Johncms\Auth\AuthMethod;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentAuthSessionRepository;
use Johncms\Auth\AuthTables;
use Johncms\Auth\Impersonation\ImpersonationSettings;
use Johncms\Auth\Session\AuthCookieFactory;
use Johncms\Auth\Session\AuthSession;
use Johncms\Auth\Session\AuthSessionManager;
use Johncms\Auth\Session\SessionRevocationReason;
use Johncms\Auth\Session\SessionSettings;
use Johncms\Http\CookieQueue;
use Johncms\Http\Request;
use Johncms\Security\ClientInfoDTO;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\RunsMigrations;

final class SessionCookieAuthenticatorTest extends TestCase
{
    use BootsInMemoryDatabase;
    use RunsMigrations;

    private const DAY = 86400;

    private AuthSessionManager $sessions;

    private CookieQueue $cookieQueue;

    private SessionCookieAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->bootDatabase();
        $this->migrate('system', 'initial_auth_schema');

        $settings = new SessionSettings();
        $this->sessions = new AuthSessionManager(new EloquentAuthSessionRepository(), $settings);
        $this->cookieQueue = new CookieQueue();
        $impersonation = new ImpersonationSettings();
        $this->authenticator = new SessionCookieAuthenticator(
            $this->sessions,
            new AuthCookieFactory($settings, $impersonation),
            $this->cookieQueue,
            $impersonation
        );
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testARequestWithoutTheCookieIsNotMine(): void
    {
        self::assertNull($this->authenticator->authenticate(Request::create('/')));
        self::assertSame([], $this->cookieQueue->all());
    }

    public function testAValidCookieIdentifiesTheUser(): void
    {
        $issued = $this->sessions->start(7, true, $this->client());

        $identity = $this->authenticator->authenticate($this->requestWith($issued->token));

        self::assertNotNull($identity);
        self::assertSame(7, $identity->userId);
        self::assertSame(AuthMethod::Session, $identity->method);
        self::assertFalse($identity->isImpersonating());
    }

    public function testImpersonationIsCarriedOntoTheIdentity(): void
    {
        $issued = $this->sessions->start(7, false, $this->client(), impersonatorId: 9);

        $identity = $this->authenticator->authenticate($this->requestWith($issued->token));

        self::assertNotNull($identity);
        self::assertTrue($identity->isImpersonating());
        self::assertSame(9, $identity->impersonatorId);
    }

    /**
     * A cookie left over from a session that was signed out or expired must not keep being sent
     * on every request for the next year.
     */
    public function testAStaleCookieIsDroppedFromTheBrowser(): void
    {
        $issued = $this->sessions->start(7, true, $this->client());
        $this->sessions->revoke($issued->session, SessionRevocationReason::Logout);

        $identity = $this->authenticator->authenticate($this->requestWith($issued->token));

        self::assertNull($identity);
        self::assertCount(1, $this->cookieQueue->all());
        self::assertSame('', $this->cookieQueue->all()[0]->getValue());
    }

    public function testAnUnknownCookieIsAlsoDropped(): void
    {
        self::assertNull($this->authenticator->authenticate($this->requestWith('nonsense')));

        self::assertCount(1, $this->cookieQueue->all());
    }

    /**
     * The failure this guards against: the row slides forward while the browser keeps the
     * cookie it was given at sign-in, so the visitor is signed out exactly one lifetime after
     * signing in — no matter how often they came back.
     */
    public function testExtendingTheSessionReissuesTheCookie(): void
    {
        $issued = $this->sessions->start(7, true, $this->client(), now: time() - 10 * self::DAY);

        $this->authenticator->authenticate($this->requestWith($issued->token));

        $queued = $this->cookieQueue->all();
        self::assertCount(1, $queued);
        self::assertSame($issued->token, $queued[0]->getValue());
        self::assertGreaterThan(time() + 29 * self::DAY, $queued[0]->getExpiresTime());
    }

    /**
     * Within the throttle window nothing is written, so nothing has to be reissued either.
     */
    public function testAFreshSessionQueuesNoCookie(): void
    {
        $issued = $this->sessions->start(7, true, $this->client());

        $this->authenticator->authenticate($this->requestWith($issued->token));

        self::assertSame([], $this->cookieQueue->all());
    }

    public function testTheReissuedCookieFollowsTheSchemeOfTheRequest(): void
    {
        $issued = $this->sessions->start(7, true, $this->client(), now: time() - 10 * self::DAY);

        $this->authenticator->authenticate(
            Request::create('https://example.com/', 'GET', [], [(new SessionSettings())->cookieName => $issued->token])
        );

        self::assertTrue($this->cookieQueue->all()[0]->isSecure());
    }

    /**
     * The hour an impersonated session gets is the hour it lasts: an administrator who keeps
     * clicking must not slide it forward the way an ordinary visit does.
     */
    public function testAnImpersonatedSessionIsNotExtended(): void
    {
        $issued = $this->sessions->start(
            7,
            false,
            $this->client(),
            impersonatorId: 1,
            now: time() - 3600
        );
        $expiresAt = $issued->session->expires_at;

        $identity = $this->authenticator->authenticate($this->requestWith($issued->token));

        self::assertSame(1, $identity?->impersonatorId);
        self::assertSame([], $this->cookieQueue->all());
        self::assertSame($expiresAt, AuthSession::query()->findOrFail($issued->session->id)->expires_at);
    }

    /**
     * What makes "the administrator closed the tab" a non-problem: the impersonated session dies
     * on its own and the next request finds them being themselves again.
     */
    public function testADeadImpersonationFallsBackToTheParentSession(): void
    {
        $admin = $this->sessions->start(1, true, $this->client());
        $dead = $this->sessions->start(7, false, $this->client(), impersonatorId: 1, lifetimeOverride: 1, now: time() - 10);

        $identity = $this->authenticator->authenticate(
            Request::create('/', 'GET', [], [
                (new SessionSettings())->cookieName => $dead->token,
                (new ImpersonationSettings())->parentCookieName => $admin->token,
            ])
        );

        self::assertSame(1, $identity?->userId);
        self::assertFalse($identity->isImpersonating());
        self::assertSame($admin->token, $this->cookieValue((new SessionSettings())->cookieName));
        self::assertSame('', $this->cookieValue((new ImpersonationSettings())->parentCookieName));
    }

    private function cookieValue(string $name): ?string
    {
        foreach ($this->cookieQueue->all() as $cookie) {
            if ($cookie->getName() === $name) {
                return $cookie->getValue();
            }
        }

        return null;
    }

    private function requestWith(string $token): Request
    {
        return Request::create('/', 'GET', [], [(new SessionSettings())->cookieName => $token]);
    }

    private function client(): ClientInfoDTO
    {
        return new ClientInfoDTO('192.0.2.10', '', 'Mozilla/5.0');
    }
}
