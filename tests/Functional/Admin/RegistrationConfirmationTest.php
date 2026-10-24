<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Users\IpHistory;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The accounts waiting for approval: they are let in one by one or all at once, and removing them
 * is confirmed in a dialog saying what would go and takes nothing but them. Every action returns
 * to the page of the list it was taken from.
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

        $message = sprintf(
            dn__('admin', '%1$d account waiting for approval registered from %2$s is removed for good.', '%1$d accounts waiting for approval registered from %2$s are removed for good.', 2),
            2,
            '10.0.0.66'
        );
        self::assertStringContainsString($message, $body);
        self::assertStringContainsString('name="ip" value="' . $ip . '"', $body);
    }

    public function testTheDialogOfAllCountsThem(): void
    {
        FunctionalUserFactory::create(['preg' => 0]);
        FunctionalUserFactory::create(['preg' => 0]);
        FunctionalUserFactory::create(['preg' => 0]);

        $body = $this->dialog('/admin/registrations/delete-all');

        $message = sprintf(dn__('admin', '%d account waiting for approval is removed for good.', '%d accounts waiting for approval are removed for good.', 3), 3);
        self::assertStringContainsString($message, $body);
    }

    public function testAnAccountLetInMeanwhileHasNothingToConfirm(): void
    {
        $member = FunctionalUserFactory::create();

        $body = $this->dialog('/admin/registrations/' . $member->id . '/delete');

        self::assertStringContainsString(d__('admin', 'The account is not waiting for approval any more'), $body);
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

    public function testApprovingAllLetsInEveryAccountWaiting(): void
    {
        $first = FunctionalUserFactory::create(['preg' => 0]);
        $second = FunctionalUserFactory::create(['preg' => 0]);

        $this->post('/admin/registrations/approve-all', ['back' => '/admin/users?status=pending']);

        foreach ([$first, $second] as $user) {
            $user = User::query()->findOrFail($user->id);
            self::assertTrue($user->preg);
            self::assertNotSame('', (string) $user->regadm, 'The account says who let it in.');
        }
    }

    public function testRemovingByAddressTakesOnlyTheAccountsWaitingFromIt(): void
    {
        $ip = (int) ip2long('10.0.0.66');
        $waiting = FunctionalUserFactory::create(['preg' => 0, 'ip' => $ip]);
        Fixture::insert('cms_users_iphistory', ['user_id' => $waiting->id, 'ip' => $ip, 'ip_via_proxy' => 0, 'time' => time()]);
        $elsewhere = FunctionalUserFactory::create(['preg' => 0, 'ip' => (int) ip2long('10.0.0.67')]);
        $approved = FunctionalUserFactory::create(['preg' => 1, 'ip' => $ip]);

        $this->post('/admin/registrations/delete-by-ip', ['ip' => (string) $ip]);

        self::assertNull(User::query()->find($waiting->id));
        self::assertSame(0, IpHistory::query()->where('user_id', $waiting->id)->count(), 'The history of a removed account goes with it.');
        self::assertNotNull(User::query()->find($elsewhere->id));
        self::assertNotNull(User::query()->find($approved->id), 'An account let in already is not one the action removes.');
    }

    public function testRemovingAllLeavesTheAccountsLetInAlready(): void
    {
        $waiting = FunctionalUserFactory::create(['preg' => 0]);
        $member = FunctionalUserFactory::create();

        $this->post('/admin/registrations/delete-all', []);

        self::assertNull(User::query()->find($waiting->id));
        self::assertNotNull(User::query()->find($member->id));
    }

    /**
     * Moderating registrations is part of the panel itself: whoever may open it may let accounts
     * in, remove the ones nobody has let in, and confirm an e-mail for its owner.
     */
    public function testAccessToThePanelIsEnoughToModerateRegistrations(): void
    {
        $staff = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS]);
        $waiting = FunctionalUserFactory::create(['preg' => 0]);

        $response = $this->handleRequest(
            '/admin/registrations/approve',
            'POST',
            $this->withCsrfToken(['id' => (string) $waiting->id, 'back' => '/admin/users?status=pending']),
            cookies: $this->actingAs($staff)
        );

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertTrue(User::query()->findOrFail($waiting->id)->preg);
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
