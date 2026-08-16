<?php

declare(strict_types=1);

namespace Tests\Functional\Http;

use Johncms\Http\Controller\ExternalAuthController;
use Johncms\Http\Request;
use Johncms\Router\SymfonyRouteMatcher;
use Symfony\Component\Routing\RouteCollection;
use Tests\Functional\FunctionalTestCase;

/**
 * The routes of the external sign-in, and the order they are declared in.
 *
 * `complete` matches the provider pattern as well, and the matcher answers with the first route
 * that fits. Declared after the parameterised one, the last screen of signing up would be
 * "start signing in with the service called complete" — which is what it did, and the message it
 * showed ("this sign-in service is not available") pointed nowhere near the cause.
 */
final class ExternalAuthRoutesTest extends FunctionalTestCase
{
    public function testTheFinishScreenIsNotSwallowedByTheProviderRoute(): void
    {
        $match = $this->container()->get(SymfonyRouteMatcher::class)->matchRequest(Request::create('/auth/complete'));

        self::assertSame(
            [ExternalAuthController::class, 'profileForm'],
            $match->handler
        );
    }

    public function testAProviderKeyStillReachesTheStartAction(): void
    {
        $match = $this->container()->get(SymfonyRouteMatcher::class)->matchRequest(Request::create('/auth/vk'));

        self::assertSame(
            [ExternalAuthController::class, 'start'],
            $match->handler
        );
        self::assertSame('vk', $match->params['provider'] ?? null);
    }

    public function testTheFixedPathsAreDeclaredBeforeTheParameterisedOne(): void
    {
        /** @var RouteCollection $routes */
        $routes = $this->container()->get(RouteCollection::class);

        $names = array_keys(iterator_to_array($routes));
        $complete = array_search('auth.external.complete', $names, true);
        $start = array_search('auth.external.start', $names, true);

        self::assertIsInt($complete);
        self::assertIsInt($start);
        self::assertLessThan($start, $complete);
    }
}
