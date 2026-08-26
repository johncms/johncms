<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Johncms\Auth\Authorization\CorePermissions;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * The screen through which code arrives on the site.
 *
 * What is checked here is mostly who is refused: the permission behind it is carried by no
 * built-in role, and every operation goes through a confirmation rather than a link.
 */
final class ModulesSectionTest extends FunctionalTestCase
{
    public function testAGuestIsSentToSignIn(): void
    {
        $response = $this->handleRequest('/admin/modules');

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }

    /**
     * An administrator is not automatically somebody who may install code: the permission is
     * granted deliberately or not at all.
     */
    public function testAnAdministratorWithoutThePermissionIsRefused(): void
    {
        $admin = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS]);

        $response = $this->handleRequest('/admin/modules', cookies: $this->actingAs($admin));

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testTheOneHoldingThePermissionSeesTheInstalledModules(): void
    {
        $response = $this->handleRequest('/admin/modules', cookies: $this->actingAs($this->manager()));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $body = (string) $response->getContent();
        self::assertStringContainsString('johncms/forum', $body);
        self::assertStringContainsString('johncms/admin', $body);
    }

    /**
     * The system module has no "remove" and no "switch off" — the buttons are not drawn at all,
     * and the service refuses even if the request is made by hand.
     */
    public function testTheSystemModuleOffersNoWayToRemoveIt(): void
    {
        $response = $this->handleRequest('/admin/modules', cookies: $this->actingAs($this->manager()));

        $body = (string) $response->getContent();

        self::assertStringNotContainsString('module=johncms%2Fadmin&operation=uninstall', $body);
        self::assertStringNotContainsString('module=johncms%2Fadmin&operation=disable', $body);
    }

    public function testAnOperationIsConfirmedBeforeItRuns(): void
    {
        $response = $this->handleRequest(
            '/admin/modules/confirm?module=johncms%2Fnews&operation=disable',
            cookies: $this->actingAs($this->manager())
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $body = (string) $response->getContent();
        self::assertStringContainsString('csrf_token', $body, 'The confirmation posts a form.');
        self::assertStringContainsString('johncms/news', $body);
    }

    public function testAnUnknownModuleIsNotConfirmed(): void
    {
        $response = $this->handleRequest(
            '/admin/modules/confirm?module=vasya%2Fnothing&operation=disable',
            cookies: $this->actingAs($this->manager())
        );

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/admin/modules', $response->headers->get('Location'));
    }

    public function testTheUploadFormIsBehindTheSamePermission(): void
    {
        $refused = $this->handleRequest(
            '/admin/modules/upload',
            cookies: $this->actingAs(FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS]))
        );
        self::assertSame(Response::HTTP_FORBIDDEN, $refused->getStatusCode());

        $allowed = $this->handleRequest('/admin/modules/upload', cookies: $this->actingAs($this->manager()));
        self::assertSame(Response::HTTP_OK, $allowed->getStatusCode());
        self::assertStringContainsString('multipart/form-data', (string) $allowed->getContent());
    }

    private function manager(): \Johncms\Users\User
    {
        return FunctionalUserFactory::createWithPermissions([
            CorePermissions::ADMIN_ACCESS,
            CorePermissions::MODULES_MANAGE,
        ]);
    }
}
