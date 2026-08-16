<?php

declare(strict_types=1);

namespace Tests\Functional\Http;

use Johncms\Auth\Events\AuthEvent;
use Johncms\Auth\Events\AuthEventType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouteCollection;
use Tests\Functional\FunctionalTestCase;

/**
 * The sign-in log screen, end to end.
 *
 * The audit trail is only worth writing if somebody can read it, so the page is exercised the
 * way it is opened in the panel: through the real session, the real permission gate and the real
 * template.
 */
final class AuthLogPageTest extends FunctionalTestCase
{
    private const URL = '/admin/auth-log';

    /** @var list<int> Entries written by this test, removed again in tearDown(). */
    private array $storedIds = [];

    protected function tearDown(): void
    {
        if ($this->storedIds !== []) {
            AuthEvent::query()->whereIn('id', $this->storedIds)->delete();
            $this->storedIds = [];
        }

        parent::tearDown();
    }

    public function testAGuestIsSentToSignIn(): void
    {
        $response = $this->handleRequest(self::URL);

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/login', $response->headers->get('Location'));
    }

    public function testTheRouteIsBehindTheReadingPermission(): void
    {
        /** @var RouteCollection $routes */
        $routes = $this->container()->get(RouteCollection::class);

        self::assertSame(
            'admin.auth_log.view',
            $routes->get('admin.auth_log')?->getDefault('_permission')
        );
    }

    public function testTheAdministratorSeesTheEntries(): void
    {
        $userId = $this->supervisorId();
        // Asserted through the context rather than through the name of the event: the stand runs
        // in its own language, and the label is translated.
        $this->store(AuthEventType::LoginSuccess, $userId, ['marker' => 'functional_test_entry']);

        $response = $this->handleRequest(self::URL, cookies: $this->actingAs($userId));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('functional_test_entry', (string) $response->getContent());
    }

    /**
     * Narrowing by kind is what makes the screen usable at all once a site has been running for a
     * while: refused attempts outnumber everything else.
     */
    public function testTheListCanBeNarrowedToOneKindOfEvent(): void
    {
        $userId = $this->supervisorId();
        $this->store(AuthEventType::LoginFailed, $userId, ['reason' => 'functional_test_marker']);
        $this->store(AuthEventType::LoginSuccess, $userId);

        $response = $this->handleRequest(
            self::URL . '?event=' . AuthEventType::LoginFailed->value,
            cookies: $this->actingAs($userId)
        );

        self::assertStringContainsString('functional_test_marker', (string) $response->getContent());
    }

    /**
     * @param array<string, mixed> $context
     */
    private function store(AuthEventType $event, int $userId, array $context = []): void
    {
        $entry = AuthEvent::query()->create(
            [
                'user_id'    => $userId,
                'event'      => $event->value,
                'ip'         => '127.0.0.1',
                'user_agent' => 'phpunit',
                'context'    => $context === [] ? null : $context,
                'created_at' => time(),
            ]
        );

        $this->storedIds[] = $entry->id;
    }

    /**
     * The account of the local stand that may read the log. Without one there is nothing to check
     * the screen with, and inventing an administrator would mean writing roles into the database
     * of the stand.
     */
    private function supervisorId(): int
    {
        $id = AuthEvent::query()
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
}
