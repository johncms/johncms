<?php

declare(strict_types=1);

namespace Tests\Functional\Users;

use Johncms\Auth\CurrentUser;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * The current-user service is shared, and hundreds of controllers take it in their constructor —
 * so it cannot be rebuilt per request; what it answers is dropped between requests instead. This
 * asserts both halves of that: the same service survives, and who it answers with belongs to the
 * request being served rather than to the previous one.
 *
 * A worker runtime is where this matters; under FPM the process ends with the request. That is
 * also why the test drives two requests through one process instead of asserting on a page.
 */
final class CurrentUserPerRequestTest extends FunctionalTestCase
{
    public function testTheVisitorOfEachRequestIsTheOneAnswered(): void
    {
        $userId = $this->createUser();

        $currentUser = $this->container()->get(CurrentUser::class);

        $this->handleRequest('/', cookies: $this->actingAs($userId));

        self::assertSame($userId, $currentUser->id(), 'The service must answer with the visitor of the request.');
        self::assertSame($userId, $currentUser->user()->id);
        self::assertTrue($currentUser->user()->exists);

        // A guest now: everything the previous visitor left behind has to go, or the next request
        // of a worker is answered as them.
        $this->handleRequest('/');

        self::assertSame(0, $currentUser->id(), 'The service still answers with the previous visitor.');
        self::assertFalse($currentUser->user()->exists);
        self::assertSame([], $currentUser->user()->ban);
    }

    public function testTheSharedServiceIsNeverReplaced(): void
    {
        $userId = $this->createUser();

        $currentUser = $this->container()->get(CurrentUser::class);

        $this->handleRequest('/', cookies: $this->actingAs($userId));

        // Replacing the object would leave every service that took it in its constructor with the
        // one built for the request the container was built for.
        self::assertSame($currentUser, $this->container()->get(CurrentUser::class));
    }

    /**
     * The visitor is stored with the loopback address, the one Environment falls back to when
     * there is no REMOTE_ADDR: the authentication would otherwise record an IP history entry.
     */
    private function createUser(): int
    {
        return FunctionalUserFactory::create(['ip' => sprintf('%u', ip2long('127.0.0.1'))])->id;
    }
}
