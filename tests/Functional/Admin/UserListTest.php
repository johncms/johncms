<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Johncms\Auth\Authorization\CorePermissions;
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

    public function testTheSearchFindsAnAccountByTheAddressItCameFrom(): void
    {
        FunctionalUserFactory::create(['name' => 'Radagast', 'ip' => (int) ip2long('10.0.0.50')]);
        FunctionalUserFactory::create(['name' => 'Saruman', 'ip' => (int) ip2long('10.0.0.51')]);

        $body = $this->listOf('/admin/users?q=10.0.0.50');

        self::assertStringContainsString('Radagast', $body);
        self::assertStringNotContainsString('Saruman', $body);
    }

    public function testTheRoleFilterListsTheHoldersOfThatRole(): void
    {
        $role = FunctionalUserFactory::createRole([], 20);
        $holder = FunctionalUserFactory::create(['name' => 'Glorfindel']);
        FunctionalUserFactory::grant($holder->id, $role->slug);
        $other = FunctionalUserFactory::createWithPermissions([], ['name' => 'Haldir'], 20);

        $body = $this->listOf('/admin/users?role=' . $role->id);

        self::assertStringContainsString($holder->name, $body);
        self::assertStringNotContainsString($other->name, $body);
    }

    public function testTheActivityFilterListsByTheLastVisit(): void
    {
        FunctionalUserFactory::create(['name' => 'Treebeard', 'lastdate' => time() - 200 * 86400]);
        FunctionalUserFactory::create(['name' => 'Quickbeam', 'lastdate' => time() - 3600]);

        $lately = $this->listOf('/admin/users?activity=day');
        self::assertStringContainsString('Quickbeam', $lately);
        self::assertStringNotContainsString('Treebeard', $lately);

        $away = $this->listOf('/admin/users?activity=inactive');
        self::assertStringContainsString('Treebeard', $away);
        self::assertStringNotContainsString('Quickbeam', $away);
    }

    public function testTheRegistrationFilterListsByTheDateOfRegistration(): void
    {
        FunctionalUserFactory::create(['name' => 'Bombadil', 'datereg' => time() - 400 * 86400]);
        FunctionalUserFactory::create(['name' => 'Goldberry', 'datereg' => time() - 3 * 86400]);

        $body = $this->listOf('/admin/users?registered=week');

        self::assertStringContainsString('Goldberry', $body);
        self::assertStringNotContainsString('Bombadil', $body);
    }

    public function testTheListIsSortedByTheColumnAskedFor(): void
    {
        FunctionalUserFactory::create(['name' => 'Meriadoc', 'karma_plus' => 1]);
        FunctionalUserFactory::create(['name' => 'Peregrin', 'karma_plus' => 9]);
        FunctionalUserFactory::create(['name' => 'Fredegar', 'karma_plus' => 5, 'karma_minus' => 1]);

        $byKarma = $this->listOf('/admin/users?q=r&sort=karma');
        self::assertLessThan(strpos($byKarma, 'Fredegar'), strpos($byKarma, 'Peregrin'), 'The highest karma comes first.');
        self::assertLessThan(strpos($byKarma, 'Meriadoc'), strpos($byKarma, 'Fredegar'));

        $byNick = $this->listOf('/admin/users?q=r&sort=nick');
        self::assertLessThan(strpos($byNick, 'Meriadoc'), strpos($byNick, 'Fredegar'), 'The nicknames go from A to Z.');
        self::assertLessThan(strpos($byNick, 'Peregrin'), strpos($byNick, 'Meriadoc'));
    }

    public function testAPageBeyondTheEndOfTheListLeadsToItsLastPage(): void
    {
        $response = $this->handleRequest('/admin/users?page=999', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertStringNotContainsString('page=999', (string) $response->headers->get('Location'));
    }

    public function testAHiddenAddressAndTheOriginAreForWhoeverMayReadThem(): void
    {
        FunctionalUserFactory::create(['name' => 'Thranduil', 'mail' => 'hidden@example.com', 'mailvis' => 0]);
        $moderator = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS], level: 20);

        $forModerator = $this->listOf('/admin/users?q=Thranduil', $moderator);
        self::assertStringContainsString('Thranduil', $forModerator);
        self::assertStringNotContainsString('hidden@example.com', $forModerator);
        self::assertStringNotContainsString('/admin/ip-search?ip=', $forModerator);

        $forSupervisor = $this->listOf('/admin/users?q=Thranduil');
        self::assertStringContainsString('hidden@example.com', $forSupervisor);
        self::assertStringContainsString('/admin/ip-search?ip=', $forSupervisor);
    }

    /**
     * Each number above the list is the size of the list it opens.
     */
    public function testTheNumbersAboveTheListAgreeWithTheListsTheyOpen(): void
    {
        FunctionalUserFactory::create(['preg' => 0]);
        FunctionalUserFactory::create(['datereg' => time() - 3600]);
        $this->ban(FunctionalUserFactory::create(), time() + 3600);
        $supervisor = FunctionalUserFactory::createSupervisor();

        $page = (string) $this->handleRequest('/admin/users', cookies: $this->actingAs($supervisor))->getContent();
        preg_match_all('~<a class="admin-stat[^"]*" href="([^"]+)">\s*<span class="admin-stat__label">[^<]*</span>\s*<span class="admin-stat__value">([^<]+)</span>~', $page, $cards, PREG_SET_ORDER);

        self::assertCount(5, $cards);
        foreach ($cards as [, $url, $value]) {
            $list = $this->listOf(html_entity_decode($url), $supervisor);
            $found = preg_match('~admin-list-footer__total">[^<\d]*([\d\s\x{00a0}\x{202f}]+)~u', $list, $total) === 1 ? $total[1] : '0';

            self::assertSame($this->digits($value), $this->digits($found), 'The card leading to ' . $url . ' counts what it opens.');
        }
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

        $response = $this->postForm('/admin/registrations/approve', ['id' => $waiting->id], FunctionalUserFactory::createSupervisor());

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
    private function listOf(string $uri, ?User $viewer = null): string
    {
        $response = $this->handleRequest(
            $uri,
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            cookies: $this->actingAs($viewer ?? FunctionalUserFactory::createSupervisor())
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        return (string) $response->getContent();
    }

    private function digits(string $number): int
    {
        return (int) preg_replace('~\D~u', '', $number);
    }

    private function ban(User $user, int $until): void
    {
        Fixture::insert(
            'cms_ban_users',
            ['user_id' => $user->id, 'ban_time' => $until, 'ban_while' => time() - 7200, 'ban_type' => 1, 'ban_reason' => 'Spam']
        );
    }
}
