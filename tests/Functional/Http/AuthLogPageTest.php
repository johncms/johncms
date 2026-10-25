<?php

declare(strict_types=1);

namespace Tests\Functional\Http;

use Johncms\Auth\Events\AuthEvent;
use Johncms\Auth\Events\AuthEventType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouteCollection;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

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
        $userId = FunctionalUserFactory::createSupervisor()->id;
        // Asserted through the context rather than through the name of the event: the label of an
        // event is translated, and the language depends on the configuration.
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
        $userId = FunctionalUserFactory::createSupervisor()->id;
        $this->store(AuthEventType::LoginFailed, $userId, ['reason' => 'functional_test_marker']);
        $this->store(AuthEventType::LoginSuccess, $userId);

        $response = $this->handleRequest(
            self::URL . '?event=' . AuthEventType::LoginFailed->value,
            cookies: $this->actingAs($userId)
        );

        self::assertStringContainsString('functional_test_marker', (string) $response->getContent());
    }

    public function testTheLiveListIsAnsweredWithItsRegionsAlone(): void
    {
        $userId = FunctionalUserFactory::createSupervisor()->id;
        $this->store(AuthEventType::LoginSuccess, $userId);

        $response = $this->handleRequest(
            self::URL . '?q=127.0.0.1',
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            cookies: $this->actingAs($userId)
        );
        $body = (string) $response->getContent();

        self::assertStringContainsString('data-live-list-region="results"', $body);
        self::assertStringContainsString('data-live-list-region="filters"', $body);
        self::assertStringNotContainsString('<html', $body);
    }

    /**
     * The page of an account links here with its number; the chip names the account and takes
     * the narrowing back.
     */
    public function testTheLogOfOneAccountNamesItInAChip(): void
    {
        $viewer = FunctionalUserFactory::createSupervisor();
        $member = FunctionalUserFactory::create(['name' => 'Peregrin']);
        $this->store(AuthEventType::LoginSuccess, $member->id);

        $body = (string) $this->handleRequest(self::URL . '?user=' . $member->id, cookies: $this->actingAs($viewer))->getContent();

        self::assertMatchesRegularExpression('#<a class="admin-chip" href="/admin/auth-log"[^>]*>.*?Peregrin#s', $body);
    }

    public function testAnEntryLeadsToTheAccountAndToEverythingFromItsAddress(): void
    {
        $viewer = FunctionalUserFactory::createSupervisor();
        $member = FunctionalUserFactory::create(['name' => 'Meriadoc']);
        $this->store(AuthEventType::LoginSuccess, $member->id);

        $body = (string) $this->handleRequest(self::URL, cookies: $this->actingAs($viewer))->getContent();

        self::assertStringContainsString('href="/admin/users/' . $member->id . '">Meriadoc</a>', $body);
        self::assertStringContainsString('href="/admin/auth-log?q=127.0.0.1"', $body);
    }

    public function testAnotherKindOfEventIsLeftOut(): void
    {
        $userId = FunctionalUserFactory::createSupervisor()->id;
        $this->store(AuthEventType::LoginFailed, $userId, ['reason' => 'kept_marker']);
        $this->store(AuthEventType::LoginSuccess, $userId, ['marker' => 'left_out_marker']);

        // Asked as the live list asks: the whole page carries the query log when debugging is on,
        // and the marker of the entry left out would be found there.
        $body = (string) $this->handleRequest(
            self::URL . '?event=' . AuthEventType::LoginFailed->value,
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            cookies: $this->actingAs($userId)
        )->getContent();

        self::assertStringContainsString('kept_marker', $body);
        self::assertStringNotContainsString('left_out_marker', $body);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function store(AuthEventType $event, int $userId, array $context = []): void
    {
        AuthEvent::query()->create(
            [
                'user_id'    => $userId,
                'event'      => $event->value,
                'ip'         => '127.0.0.1',
                'user_agent' => 'phpunit',
                'context'    => $context === [] ? null : $context,
                'created_at' => time(),
            ]
        );
    }
}
