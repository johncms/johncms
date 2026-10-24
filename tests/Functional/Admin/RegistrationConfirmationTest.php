<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * Removing accounts that wait for approval is confirmed in a dialog saying what would go, and the
 * action returns to the page of the list it was taken from.
 */
final class RegistrationConfirmationTest extends FunctionalTestCase
{
    public function testTheDialogOfOneAccountPostsItsRemoval(): void
    {
        $waiting = FunctionalUserFactory::create(['name' => 'Ugluk', 'preg' => 0]);

        $body = $this->dialog('/admin/registrations/' . $waiting->id . '/delete?back=' . rawurlencode('/admin/users?status=pending&q=Ug'));

        self::assertStringStartsWith('<form class="modal-content" method="post" action="/admin/registrations/delete">', trim($body));
        self::assertStringContainsString('name="id" value="' . $waiting->id . '"', $body);
        self::assertStringContainsString('name="back" value="/admin/users?status=pending&amp;q=Ug"', $body);
        self::assertStringContainsString('Ugluk', $body);
    }

    public function testTheDialogOfAnAddressCountsTheAccountsFromIt(): void
    {
        $ip = (int) ip2long('10.0.0.66');
        FunctionalUserFactory::create(['preg' => 0, 'ip' => $ip]);
        FunctionalUserFactory::create(['preg' => 0, 'ip' => $ip]);
        FunctionalUserFactory::create(['preg' => 0, 'ip' => (int) ip2long('10.0.0.67')]);
        // Let in already: not one of those the action removes.
        FunctionalUserFactory::create(['preg' => 1, 'ip' => $ip]);

        $body = $this->dialog('/admin/registrations/delete-by-ip?ip=' . $ip);

        self::assertStringContainsString('2 accounts waiting for approval registered from 10.0.0.66', $body);
        self::assertStringContainsString('name="ip" value="' . $ip . '"', $body);
    }

    public function testTheDialogOfAllCountsThem(): void
    {
        FunctionalUserFactory::create(['preg' => 0]);
        FunctionalUserFactory::create(['preg' => 0]);
        FunctionalUserFactory::create(['preg' => 0]);

        self::assertStringContainsString('3 accounts waiting for approval are removed for good', $this->dialog('/admin/registrations/delete-all'));
    }

    public function testAnAccountLetInMeanwhileHasNothingToConfirm(): void
    {
        $member = FunctionalUserFactory::create();

        $body = $this->dialog('/admin/registrations/' . $member->id . '/delete');

        self::assertStringContainsString('The account is not waiting for approval any more', $body);
        self::assertStringNotContainsString('<form', $body);
    }

    public function testAPlainVisitGetsTheConfirmationAsAPage(): void
    {
        FunctionalUserFactory::create(['preg' => 0]);

        $response = $this->handleRequest('/admin/registrations/delete-all', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('class="modal admin-dialog-page"', (string) $response->getContent());
    }

    public function testTheRemovalReturnsToThePageOfTheListItCameFrom(): void
    {
        $waiting = FunctionalUserFactory::create(['preg' => 0]);

        $response = $this->post('/admin/registrations/delete', ['id' => $waiting->id, 'back' => '/admin/users?status=pending&page=3']);

        self::assertSame('/admin/users?status=pending&page=3', $response->headers->get('Location'));
        self::assertNull(User::query()->find($waiting->id));
    }

    public function testAReturnAddressOutsideTheListIsNotFollowed(): void
    {
        $response = $this->post('/admin/registrations/delete-all', ['back' => 'https://example.com/admin/users']);

        self::assertSame('/admin/users?status=pending', $response->headers->get('Location'));
    }

    private function dialog(string $uri): string
    {
        $response = $this->handleRequest(
            $uri,
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        return (string) $response->getContent();
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function post(string $uri, array $parameters): Response
    {
        $response = $this->handleRequest(
            $uri,
            'POST',
            $this->withCsrfToken($parameters),
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());

        return $response;
    }
}
