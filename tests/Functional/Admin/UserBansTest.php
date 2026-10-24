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
 * The bans of an account in the panel follow the rules of the site: the same kinds for the same
 * permissions, downwards only, the record kept when a ban is lifted.
 */
final class UserBansTest extends FunctionalTestCase
{
    public function testTheHistoryListsTheBans(): void
    {
        $member = FunctionalUserFactory::create();
        $this->ban($member, BanKind::FORUM, time() + 3600, 'Flood in the forum');

        $response = $this->handleRequest('/admin/users/' . $member->id . '/bans', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('Flood in the forum', (string) $response->getContent());
    }

    public function testTheFormOpensAsADialog(): void
    {
        $member = FunctionalUserFactory::create();

        $response = $this->handleRequest(
            '/admin/users/' . $member->id . '/bans/new',
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );

        self::assertStringStartsWith('<form class="modal-content" method="post" action="/admin/users/' . $member->id . '/bans/new">', trim((string) $response->getContent()));
    }

    public function testABanIsGivenAndTheHistoryShown(): void
    {
        $member = FunctionalUserFactory::create();

        $response = $this->post('/admin/users/' . $member->id . '/bans/new', ['kind' => '11', 'amount' => '2', 'unit' => '2', 'reason' => 'Spam']);

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/admin/users/' . $member->id . '/bans', $response->headers->get('Location'));

        $ban = Ban::query()->where('user_id', $member->id)->firstOrFail();
        self::assertSame(11, (int) $ban->ban_type);
        self::assertSame(7200, $ban->ban_time - $ban->ban_while);
    }

    public function testAModeratorOfTheForumIsOfferedAndGivenOnlyTheForumBan(): void
    {
        $moderator = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS, CorePermissions::USERS_BAN_FORUM], level: 20);
        $member = FunctionalUserFactory::create();
        $cookies = $this->actingAs($moderator);

        $form = (string) $this->handleRequest('/admin/users/' . $member->id . '/bans/new', cookies: $cookies)->getContent();
        self::assertStringContainsString('id="ban-kind-11"', $form);
        self::assertStringNotContainsString('id="ban-kind-1"', $form);

        $response = $this->handleRequest(
            '/admin/users/' . $member->id . '/bans/new',
            'POST',
            $this->withCsrfToken(['kind' => '1', 'amount' => '1', 'unit' => '2', 'reason' => 'Spam']),
            cookies: $cookies
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), 'The form comes back with what stopped it.');
        self::assertSame(0, Ban::query()->where('user_id', $member->id)->count());
    }

    public function testNobodyBansSomebodyStandingAboveThem(): void
    {
        $moderator = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS, CorePermissions::USERS_BAN_MANAGE], level: 20);
        $supervisor = FunctionalUserFactory::createSupervisor();

        $response = $this->handleRequest('/admin/users/' . $supervisor->id . '/bans/new', cookies: $this->actingAs($moderator));

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testLiftingABanEndsItAndKeepsTheRecord(): void
    {
        $member = FunctionalUserFactory::create();
        $banId = $this->ban($member, BanKind::SITE, time() + 3600, 'Spam');

        $response = $this->post('/admin/users/' . $member->id . '/bans/' . $banId . '/lift', []);

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $ban = Ban::query()->findOrFail($banId);
        self::assertLessThanOrEqual(time(), $ban->ban_time);
    }

    public function testDeletingAndClearingRemoveTheRecords(): void
    {
        $member = FunctionalUserFactory::create();
        $finished = $this->ban($member, BanKind::FORUM, time() - 60, 'Old');
        $inForce = $this->ban($member, BanKind::GUESTBOOK, time() + 3600, 'Now');
        $other = $this->ban($member, BanKind::LIBRARY, time() - 120, 'Older');

        $this->post('/admin/users/' . $member->id . '/bans/' . $other . '/delete', []);
        self::assertNull(Ban::query()->find($other));

        $this->post('/admin/users/' . $member->id . '/bans/clear', []);
        self::assertNull(Ban::query()->find($finished));
        self::assertNotNull(Ban::query()->find($inForce), 'A ban in force is lifted, not cleared.');
    }

    private function ban(User $user, BanKind $kind, int $until, string $reason): int
    {
        return Fixture::insert(
            'cms_ban_users',
            ['user_id' => $user->id, 'ban_time' => $until, 'ban_while' => time() - 7200, 'ban_type' => $kind->value, 'ban_reason' => $reason, 'ban_who' => 'admin']
        );
    }

    /**
     * @param array<string, string> $parameters
     */
    private function post(string $uri, array $parameters): Response
    {
        return $this->handleRequest(
            $uri,
            'POST',
            $this->withCsrfToken($parameters),
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );
    }
}
