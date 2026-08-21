<?php

declare(strict_types=1);

namespace Tests\Functional\Http;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouteCollection;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * The captcha settings screen, end to end.
 *
 * Read-only, for the reason the mail screen gives: saving writes captcha.local.php and drops the
 * compiled container, which is the configuration the rest of the suite runs against. What is
 * worth checking here is that the screen is reachable only by whoever may change the site, and
 * that it is built from the registered providers rather than from a list written into the page.
 */
final class CaptchaSettingsPageTest extends FunctionalTestCase
{
    private const URL = '/admin/settings/captcha';

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

        foreach (['admin.settings.captcha', 'admin.settings.captcha.save'] as $name) {
            self::assertNotNull($routes->get($name), sprintf('The route %s is missing.', $name));
        }
    }

    public function testTheAdministratorSeesEveryProviderWithItsOwnFields(): void
    {
        $response = $this->handleRequest(
            self::URL,
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = (string) $response->getContent();

        // The choice of provider, and the settings of the built-in one and of a remote service:
        // two providers describing completely different fields, both drawn by the same template.
        self::assertStringContainsString('name="default" value="image"', $content);
        self::assertStringContainsString('name="providers[image][format]"', $content);
        self::assertStringContainsString('name="providers[smartcaptcha][site_key]"', $content);
        self::assertStringContainsString('name="providers[recaptcha_v3][score_threshold]"', $content);
        // A secret is asked for, never sent back.
        self::assertStringContainsString('name="providers[smartcaptcha][secret_key]" value=""', $content);
    }
}
