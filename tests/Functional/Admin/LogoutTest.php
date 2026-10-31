<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

final class LogoutTest extends FunctionalTestCase
{
    public function testConfirmationKeepsTheSessionAndUsesThePanel(): void
    {
        $cookies = $this->actingAs(FunctionalUserFactory::createSupervisor());
        $response = $this->handleRequest('/admin/logout', cookies: $cookies);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('action="/admin/logout"', (string) $response->getContent());
        self::assertStringContainsString('name="csrf_token"', (string) $response->getContent());
        self::assertSame(200, $this->handleRequest('/admin', cookies: $cookies)->getStatusCode());
    }

    public function testModalConfirmationReturnsOnlyTheDialog(): void
    {
        $response = $this->handleRequest(
            '/admin/logout',
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()),
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('action="/admin/logout"', (string) $response->getContent());
        self::assertStringNotContainsString('<html', (string) $response->getContent());
    }

    public function testLogoutRevokesTheSessionAndReturnsToAdminLogin(): void
    {
        $cookies = $this->actingAs(FunctionalUserFactory::createSupervisor());
        $response = $this->handleRequest('/admin/logout', 'POST', $this->withCsrfToken(), cookies: $cookies);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/admin/login', $response->headers->get('Location'));

        $protectedPage = $this->handleRequest('/admin', cookies: $cookies);
        self::assertSame(302, $protectedPage->getStatusCode());
        self::assertSame('/admin/login', $protectedPage->headers->get('Location'));
    }
}
