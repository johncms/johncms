<?php

declare(strict_types=1);

namespace Tests\Functional\Http;

use Johncms\Auth\Events\AuthEvent;
use Johncms\Auth\Events\AuthEventType;
use Johncms\Auth\Session\AuthSession;
use Johncms\Auth\Session\SessionSettings;
use Johncms\Security\Csrf;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouteCollection;
use Tests\Functional\FunctionalTestCase;

/**
 * Browsing as another user, driven through the real pipeline.
 *
 * The unit tests cover the manager; what is checked here is the wiring around it — the routes,
 * the permission on the way in, the absence of one on the way out, and the banner the theme
 * draws while it lasts.
 */
final class ImpersonationTest extends FunctionalTestCase
{
    /** @var list<int> Sessions opened by this test, removed again in tearDown(). */
    private array $openedSessionIds = [];

    /** @var list<int> Audit entries written by this test. */
    private array $storedEventIds = [];

    protected function tearDown(): void
    {
        if ($this->openedSessionIds !== []) {
            AuthSession::query()->whereIn('id', $this->openedSessionIds)->delete();
            $this->openedSessionIds = [];
        }

        if ($this->storedEventIds !== []) {
            AuthEvent::query()->whereIn('id', $this->storedEventIds)->delete();
            $this->storedEventIds = [];
        }

        parent::tearDown();
    }

    public function testAGuestCannotStartBrowsingAsSomebody(): void
    {
        $response = $this->handleRequest('/impersonation/start/1', 'POST', $this->withToken());

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/login', $response->headers->get('Location'));
    }

    public function testStartingIsBehindThePermissionAndLeavingIsNot(): void
    {
        /** @var RouteCollection $routes */
        $routes = $this->container()->get(RouteCollection::class);

        self::assertSame('users.impersonate', $routes->get('impersonation.start')?->getDefault('_permission'));
        // Whoever is inside an impersonated session must always be able to get out of it, and the
        // account they are browsing as holds no permissions of the staff.
        self::assertNull($routes->get('impersonation.stop')?->getDefault('_permission'));
    }

    public function testAnAdministratorBrowsesAsAUserAndComesBack(): void
    {
        $adminId = $this->supervisorId();
        $targetId = $this->ordinaryUserId($adminId);
        $adminCookies = $this->actingAs($adminId);

        $started = $this->handleRequest(
            '/impersonation/start/' . $targetId,
            'POST',
            $this->withToken(),
            cookies: $adminCookies
        );

        self::assertSame(Response::HTTP_FOUND, $started->getStatusCode());

        $cookies = $this->cookiesOf($started);
        $session = AuthSession::query()
            ->where('user_id', '=', $targetId)
            ->whereNotNull('impersonator_id')
            ->latest('id')
            ->firstOrFail();
        $this->openedSessionIds[] = $session->id;
        $this->rememberEventsOf($targetId);

        self::assertSame($adminId, $session->impersonator_id);

        // The banner is the one thing that must never be missing: writing as the user is allowed,
        // so forgetting whose account this is has to be impossible.
        $page = $this->handleRequest('/', cookies: $cookies);
        self::assertStringContainsString('/impersonation/stop', (string) $page->getContent());

        $stopped = $this->handleRequest('/impersonation/stop', 'POST', $this->withToken(), cookies: $cookies);

        self::assertSame(Response::HTTP_FOUND, $stopped->getStatusCode());
        self::assertNotNull(AuthSession::query()->findOrFail($session->id)->revoked_at);
        self::assertSame(
            $adminCookies[$this->sessionCookieName()],
            $this->cookiesOf($stopped)[$this->sessionCookieName()] ?? null
        );
    }

    /**
     * @return array<string, string>
     */
    private function withToken(): array
    {
        return ['csrf_token' => $this->container()->get(Csrf::class)->getToken()];
    }

    /**
     * The cookies a response sets, as handleRequest() takes them.
     *
     * @return array<string, string>
     */
    private function cookiesOf(Response $response): array
    {
        $cookies = [];

        foreach ($response->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = (string) $cookie->getValue();
        }

        return $cookies;
    }

    private function sessionCookieName(): string
    {
        return $this->container()->get(SessionSettings::class)->cookieName;
    }

    private function rememberEventsOf(int $userId): void
    {
        $ids = AuthEvent::query()
            ->where('user_id', '=', $userId)
            ->whereIn('event', [
                AuthEventType::ImpersonationStart->value,
                AuthEventType::ImpersonationStop->value,
                AuthEventType::ImpersonatedAction->value,
            ])
            ->pluck('id')
            ->all();

        foreach ($ids as $id) {
            $this->storedEventIds[] = (int) $id;
        }
    }

    private function supervisorId(): int
    {
        $id = AuthSession::query()
            ->getConnection()
            ->table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('roles.slug', '=', 'supervisor')
            ->value('user_roles.user_id');

        if ($id === null) {
            self::markTestSkipped('The stand has no account holding the supervisor role.');
        }

        return (int) $id;
    }

    /**
     * An account of the stand holding nothing beyond the default role, so browsing as it is
     * allowed by the hierarchy.
     */
    private function ordinaryUserId(int $exceptId): int
    {
        $id = AuthSession::query()
            ->getConnection()
            ->table('users')
            ->whereNotIn('id', function ($query): void {
                $query->select('user_id')->from('user_roles');
            })
            ->where('id', '!=', $exceptId)
            ->value('id');

        if ($id === null) {
            self::markTestSkipped('The stand has no account without granted roles.');
        }

        return (int) $id;
    }
}
