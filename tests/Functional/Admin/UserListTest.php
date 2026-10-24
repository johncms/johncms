<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The list of users of the panel: the filters narrow it, the live list gets its regions alone,
 * and what used to be pages of their own — the staff, the registrations waiting — are filters of
 * it now.
 */
final class UserListTest extends FunctionalTestCase
{
    public function testTheSearchFindsAnAccountByAnyPartOfItsNickname(): void
    {
        FunctionalUserFactory::create(['name' => 'Gandalf']);
        FunctionalUserFactory::create(['name' => 'Saruman']);

        $body = $this->listOf('/admin/users?q=ndal');

        self::assertStringContainsString('Gandalf', $body);
        self::assertStringNotContainsString('Saruman', $body);
    }

    public function testTheSearchFindsAnAccountByItsEmailOrNumber(): void
    {
        $byMail = FunctionalUserFactory::create(['name' => 'Frodo', 'mail' => 'ring-bearer@example.com']);
        $byNumber = FunctionalUserFactory::create(['name' => 'Samwise']);

        self::assertStringContainsString($byMail->name, $this->listOf('/admin/users?q=ring-bearer'));
        self::assertStringContainsString($byNumber->name, $this->listOf('/admin/users?q=' . $byNumber->id));
    }

    public function testTheStaffFilterListsOnlyTheAccountsHoldingARole(): void
    {
        $moderator = FunctionalUserFactory::createWithPermissions(['admin.access'], ['name' => 'Elrond']);
        FunctionalUserFactory::create(['name' => 'Pippin']);

        $body = $this->listOf('/admin/users?role=staff');

        self::assertStringContainsString($moderator->name, $body);
        self::assertStringNotContainsString('Pippin', $body);
    }

    public function testThePendingFilterListsTheAccountsWaitingWithTheirApproval(): void
    {
        $waiting = FunctionalUserFactory::create(['name' => 'Boromir', 'preg' => 0]);
        FunctionalUserFactory::create(['name' => 'Faramir']);

        $body = $this->listOf('/admin/users?status=pending');

        self::assertStringContainsString($waiting->name, $body);
        self::assertStringNotContainsString('Faramir', $body);
        self::assertStringContainsString('action="/admin/registrations/approve"', $body);
        self::assertStringContainsString('action="/admin/registrations/approve-all"', $body);
    }

    public function testTheBannedFilterListsTheAccountsUnderABanInForce(): void
    {
        $banned = FunctionalUserFactory::create(['name' => 'Wormtongue']);
        $forgiven = FunctionalUserFactory::create(['name' => 'Gollum']);
        $this->ban($banned, time() + 3600);
        $this->ban($forgiven, time() - 3600);

        $body = $this->listOf('/admin/users?status=banned');

        self::assertStringContainsString($banned->name, $body);
        self::assertStringNotContainsString($forgiven->name, $body);
    }

    public function testTheLiveListIsAnsweredWithItsRegionsAlone(): void
    {
        $response = $this->handleRequest(
            '/admin/users?q=Tester',
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );

        $body = (string) $response->getContent();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringNotContainsString('<html', $body);
        foreach (['tabs', 'state', 'filters', 'results'] as $region) {
            self::assertStringContainsString('data-live-list-region="' . $region . '"', $body);
        }
    }

    public function testAnApprovedAccountReturnsToTheAccountsStillWaiting(): void
    {
        $waiting = FunctionalUserFactory::create(['preg' => 0]);

        $response = $this->handleRequest(
            '/admin/registrations/approve',
            'POST',
            $this->withCsrfToken(['id' => $waiting->id]),
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/admin/users?status=pending', $response->headers->get('Location'));
        self::assertTrue(User::query()->findOrFail($waiting->id)->preg);
    }

    public function testThePagesTheListReplacedAreGone(): void
    {
        $cookies = $this->actingAs(FunctionalUserFactory::createSupervisor());

        self::assertSame(Response::HTTP_NOT_FOUND, $this->handleRequest('/admin/staff', cookies: $cookies)->getStatusCode());
        self::assertSame(Response::HTTP_NOT_FOUND, $this->handleRequest('/admin/users/by-nick', cookies: $cookies)->getStatusCode());
        self::assertNotSame(Response::HTTP_OK, $this->handleRequest('/admin/registrations', cookies: $cookies)->getStatusCode());
    }

    /**
     * The regions of the list, as the live list asks for them: the page around them carries the
     * query log of the process when debugging is on, names inserted by the test included.
     */
    private function listOf(string $uri): string
    {
        $response = $this->handleRequest(
            $uri,
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        return (string) $response->getContent();
    }

    private function ban(User $user, int $until): void
    {
        Fixture::insert(
            'cms_ban_users',
            ['user_id' => $user->id, 'ban_time' => $until, 'ban_while' => time() - 7200, 'ban_type' => 1, 'ban_reason' => 'Spam']
        );
    }
}
