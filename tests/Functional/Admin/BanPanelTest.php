<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Users\Ban;
use Johncms\Users\Bans\BanKind;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The ban panel: the accounts under a ban now, everybody who has had one, narrowed by a search
 * and by the kind, ordered by the end of the ban or by the violations.
 *
 * The lists are asked for as the live list asks: the page around them carries the query log when
 * debugging is on, and a name left out of the list would be found there.
 */
final class BanPanelTest extends FunctionalTestCase
{
    public function testTheFirstTabListsOnlyTheBansInForce(): void
    {
        $banned = FunctionalUserFactory::create(['name' => 'Gollum']);
        $this->ban($banned, BanKind::FORUM, time() + 3600);
        $forgiven = FunctionalUserFactory::create(['name' => 'Smeagol']);
        $this->ban($forgiven, BanKind::FORUM, time() - 3600);

        $active = $this->listOf('/admin/bans');
        $all = $this->listOf('/admin/bans?status=all');

        self::assertStringContainsString('Gollum', $active);
        self::assertStringNotContainsString('Smeagol', $active);
        self::assertStringContainsString('Gollum', $all);
        self::assertStringContainsString('Smeagol', $all);
    }

    public function testTheKindAndTheSearchNarrowTheList(): void
    {
        $this->ban(FunctionalUserFactory::create(['name' => 'Lotho']), BanKind::FORUM, time() + 3600);
        $this->ban(FunctionalUserFactory::create(['name' => 'Ted']), BanKind::MAIL, time() + 3600);
        $this->ban(FunctionalUserFactory::create(['name' => 'Bill']), BanKind::FORUM, time() + 3600);

        $forum = $this->listOf('/admin/bans?kind=' . BanKind::FORUM->value);
        $searched = $this->listOf('/admin/bans?q=lot');

        self::assertStringContainsString('Lotho', $forum);
        self::assertStringNotContainsString('Ted', $forum);
        self::assertStringContainsString('Lotho', $searched);
        self::assertStringNotContainsString('Bill', $searched);
    }

    public function testTheMostViolationsComeFirstWhenAskedFor(): void
    {
        $once = FunctionalUserFactory::create(['name' => 'Ferny']);
        $this->ban($once, BanKind::FORUM, time() + 7200);
        $often = FunctionalUserFactory::create(['name' => 'Sharkey']);
        $this->ban($often, BanKind::FORUM, time() - 7200);
        $this->ban($often, BanKind::MAIL, time() - 3600);
        $this->ban($often, BanKind::FORUM, time() + 3600);

        $byTime = $this->listOf('/admin/bans');
        $byViolations = $this->listOf('/admin/bans?sort=violations');

        self::assertLessThan(strpos($byTime, 'Sharkey'), strpos($byTime, 'Ferny'), 'The ban that ends last comes first.');
        self::assertLessThan(strpos($byViolations, 'Ferny'), strpos($byViolations, 'Sharkey'));
    }

    /**
     * The amnesty ends every ban in force at the same moment, so an account with two of them has
     * two that end together — and is still one row.
     */
    public function testTwoBansEndingTogetherMakeOneRow(): void
    {
        $member = FunctionalUserFactory::create(['name' => 'Rosie']);
        $until = time() + 3600;
        $this->ban($member, BanKind::FORUM, $until);
        $this->ban($member, BanKind::MAIL, $until);

        self::assertSame(1, substr_count($this->listOf('/admin/bans'), '>Rosie</a>'));
    }

    public function testALiftedBanReturnsToThePanel(): void
    {
        $member = FunctionalUserFactory::create();
        $ban = $this->ban($member, BanKind::FORUM, time() + 3600);

        $response = $this->postForm('/admin/users/' . $member->id . '/bans/' . $ban . '/lift', ['back' => '/admin/bans?status=all'], FunctionalUserFactory::createSupervisor());

        self::assertSame('/admin/bans?status=all', $response->headers->get('Location'));
        self::assertLessThanOrEqual(time(), Ban::query()->findOrFail($ban)->ban_time);
    }

    public function testAReturnAddressOutsideThePanelIsNotFollowed(): void
    {
        $member = FunctionalUserFactory::create();
        $ban = $this->ban($member, BanKind::FORUM, time() + 3600);

        $response = $this->postForm('/admin/users/' . $member->id . '/bans/' . $ban . '/lift', ['back' => 'https://example.com/'], FunctionalUserFactory::createSupervisor());

        self::assertSame('/admin/users/' . $member->id . '/bans', $response->headers->get('Location'));
    }

    public function testTheAmnestyIsAskedInTheDialog(): void
    {
        $response = $this->handleRequest(
            '/admin/bans/amnesty',
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringStartsWith('<form class="modal-content" method="post" action="/admin/bans/amnesty">', trim((string) $response->getContent()));
    }

    public function testALiftIsOfferedForABanInForceToWhoeverMayLift(): void
    {
        $inForce = $this->ban(FunctionalUserFactory::create(), BanKind::FORUM, time() + 3600);
        $finished = $this->ban(FunctionalUserFactory::create(), BanKind::FORUM, time() - 3600);
        $moderator = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS, CorePermissions::USERS_BAN_MANAGE], level: 60);

        $forSupervisor = $this->listOf('/admin/bans?status=all');
        $forModerator = $this->listOf('/admin/bans?status=all', $moderator);

        self::assertStringContainsString('/bans/' . $inForce . '/lift?back=', $forSupervisor);
        self::assertStringNotContainsString('/bans/' . $finished . '/lift', $forSupervisor);
        self::assertStringNotContainsString('/lift?back=', $forModerator, 'Lifting a ban is a permission of its own.');
    }

    public function testWhoGaveABanIsShownToWhoeverGivesThem(): void
    {
        $this->ban(FunctionalUserFactory::create(), BanKind::FORUM, time() + 3600, by: 'Elrond');
        $staff = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS], level: 20);

        self::assertStringContainsString('Elrond', $this->listOf('/admin/bans'));
        self::assertStringNotContainsString('Elrond', $this->listOf('/admin/bans', $staff));
    }

    public function testTheLiftOpenedFromThePanelClosesToIt(): void
    {
        $member = FunctionalUserFactory::create();
        $ban = $this->ban($member, BanKind::FORUM, time() + 3600);

        $response = $this->handleRequest(
            '/admin/users/' . $member->id . '/bans/' . $ban . '/lift?back=' . rawurlencode('/admin/bans?status=all'),
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );

        self::assertStringContainsString('href="/admin/bans?status=all" class="btn-close"', (string) $response->getContent());
    }

    private function ban(User $user, BanKind $kind, int $until, string $by = 'admin'): int
    {
        return Fixture::insert('cms_ban_users', [
            'user_id'    => $user->id,
            'ban_time'   => $until,
            'ban_while'  => $until - 86400,
            'ban_type'   => $kind->value,
            'ban_reason' => 'Test',
            'ban_who'    => $by,
        ]);
    }

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
}
