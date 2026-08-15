<?php

declare(strict_types=1);

namespace Tests\Functional\Http;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouteCollection;
use Tests\Functional\FunctionalTestCase;

/**
 * The gate a route declares with Route::permission(), end to end.
 *
 * These routes used not to exist for a visitor who was not allowed them — the condition around
 * their declaration was evaluated per request, against the visitor. They are declared once now,
 * and the refusal comes from the pipeline; this is what pins that the two are wired together.
 */
final class RoutePermissionTest extends FunctionalTestCase
{
    public function testAGuestIsSentToSignInInsteadOfA404(): void
    {
        $response = $this->handleRequest('/admin/news');

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/login', $response->headers->get('Location'));
    }

    /**
     * The route exists for everybody now, which is the point of the change: whoever may not open
     * it is refused by the middleware rather than by the absence of a route.
     */
    public function testTheRouteIsRegisteredWhoeverIsAsking(): void
    {
        /** @var RouteCollection $routes */
        $routes = $this->container()->get(RouteCollection::class);

        self::assertNotNull($routes->get('news.admin.index'));
        self::assertSame(
            'news.manage',
            $routes->get('news.admin.index')->getDefault('_permission')
        );
    }

    /**
     * Uploading a picture for a comment is worth what writing one is worth, and a guest holds
     * neither.
     */
    public function testAnEndpointBehindAPermissionRefusesAGuest(): void
    {
        $response = $this->handleRequest('/news/comments/upload_file');

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/login', $response->headers->get('Location'));
    }

    /**
     * A route that only asks for a session behaves the same way, without naming a permission.
     */
    public function testARouteRequiringOnlyASessionSendsAGuestToSignIn(): void
    {
        $response = $this->handleRequest('/forum/new-topic/1');

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/login', $response->headers->get('Location'));
    }
}
