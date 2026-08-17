<?php

declare(strict_types=1);

namespace Tests\Functional\Http;

use Illuminate\Database\Capsule\Manager as Capsule;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouteCollection;
use Tests\Functional\FunctionalTestCase;

/**
 * The mail settings screen, end to end.
 *
 * Read-only on purpose: saving writes mail.local.php and drops the compiled container, which is
 * the configuration of the stand the rest of the suite runs against. What is worth checking here
 * is that the screen is reachable only by whoever may change the site, and that it shows the
 * settings actually in force.
 */
final class MailSettingsPageTest extends FunctionalTestCase
{
    private const URL = '/admin/settings/mail';

    public function testAGuestIsSentToSignIn(): void
    {
        $response = $this->handleRequest(self::URL);

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        // The screens that change the site as a whole sign in through the panel of their own.
        self::assertSame('/admin/login', $response->headers->get('Location'));
    }

    public function testTheRouteIsBehindTheSettingsPermission(): void
    {
        /** @var RouteCollection $routes */
        $routes = $this->container()->get(RouteCollection::class);

        foreach (['admin.settings.mail', 'admin.settings.mail.save', 'admin.settings.mail.test'] as $name) {
            self::assertNotNull($routes->get($name), sprintf('The route %s is missing.', $name));
        }
    }

    public function testTheAdministratorSeesTheSettingsInForce(): void
    {
        $response = $this->handleRequest(self::URL, cookies: $this->actingAs($this->supervisorId()));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = (string) $response->getContent();
        // The fields of the form, and the transport the stand is configured with.
        self::assertStringContainsString('name="dsn"', $content);
        self::assertStringContainsString('name="transport"', $content);
        self::assertStringContainsString('name="redirect_to"', $content);
        self::assertStringContainsString('name="test_email"', $content);
        self::assertStringContainsString(
            sprintf('value="%s" selected="selected"', (string) (config('mail')['transport'] ?? 'sendmail')),
            $content
        );
    }

    /**
     * The account of the local stand that may change the settings of the site.
     */
    private function supervisorId(): int
    {
        $id = Capsule::connection()
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
