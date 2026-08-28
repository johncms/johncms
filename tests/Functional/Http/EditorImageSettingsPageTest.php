<?php

declare(strict_types=1);

namespace Tests\Functional\Http;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouteCollection;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * The screen that limits what the text editor may upload, end to end.
 *
 * Read-only, for the reason the mail and captcha screens give: saving writes system.local.php
 * and drops the compiled container, which is the configuration the rest of the suite runs
 * against. What is checked here is that the screen is reachable only by whoever may change the
 * site, and that it asks for every value the uploads are limited by.
 */
final class EditorImageSettingsPageTest extends FunctionalTestCase
{
    private const URL = '/admin/settings/images';

    public function testAGuestIsSentToSignIn(): void
    {
        $response = $this->handleRequest(self::URL);

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/admin/login', $response->headers->get('Location'));
    }

    public function testTheRouteIsBehindTheSettingsPermission(): void
    {
        /** @var RouteCollection $routes */
        $routes = $this->container()->get(RouteCollection::class);

        foreach (['admin.settings.images', 'admin.settings.images.save'] as $name) {
            self::assertNotNull($routes->get($name), sprintf('The route %s is missing.', $name));
        }
    }

    public function testTheAdministratorIsAskedForEveryLimit(): void
    {
        $response = $this->handleRequest(
            self::URL,
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = (string) $response->getContent();

        self::assertStringContainsString('name="max_size"', $content);
        self::assertStringContainsString('name="max_width"', $content);
        self::assertStringContainsString('name="max_height"', $content);
        self::assertStringContainsString('name="quality"', $content);
        self::assertStringContainsString('name="convert" value="webp"', $content);
    }
}
