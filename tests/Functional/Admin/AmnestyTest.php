<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Users\Ban;
use Johncms\Users\Bans\BanDurationUnit;
use Johncms\Users\Bans\BanKind;
use Johncms\Users\Karma;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The amnesty: ending the bans in force, or deleting every ban — each the way deleting it alone
 * does, so the karma it cost comes back.
 */
final class AmnestyTest extends FunctionalTestCase
{
    public function testUnbanningEndsTheBansInForceButThoseUntilLifted(): void
    {
        $member = FunctionalUserFactory::create();
        $temporary = $this->ban($member, BanKind::FORUM, time() + 3600);
        $untilLifted = $this->ban($member, BanKind::SITE, time() + BanDurationUnit::UNTIL_LIFTED->seconds(1));
        $finished = $this->ban($member, BanKind::MAIL, time() - 3600);

        $response = $this->grant(term: 0);

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertLessThanOrEqual(time(), $this->banOf($temporary)->ban_time);
        self::assertNotSame('', $this->banOf($temporary)->ban_raz, 'The history says the amnesty ended it.');
        self::assertGreaterThan(time(), $this->banOf($untilLifted)->ban_time);
        self::assertNotNull(Ban::query()->find($finished), 'The history stays.');
    }

    public function testClearingDeletesEveryBanAndGivesBackTheKarmaTheyCost(): void
    {
        $member = FunctionalUserFactory::create(['karma_minus' => 10 + 3]);
        $inForce = $this->ban($member, BanKind::FORUM, time() + 3600, penalty: 10);
        $finished = $this->ban($member, BanKind::MAIL, time() - 3600);
        // A minus another member voted is not the price of a ban, and stays.
        Fixture::insert('karma_users', ['karma_user' => $member->id, 'user_id' => FunctionalUserFactory::create()->id, 'points' => 3, 'type' => 0, 'time' => time() - 600]);

        $this->grant(term: 1);

        self::assertNull(Ban::query()->find($inForce));
        self::assertNull(Ban::query()->find($finished));
        self::assertSame(0, Karma::query()->where('karma_user', '=', $member->id)->where('user_id', '=', 0)->count());
        self::assertSame(3, (int) User::query()->whereKey($member->id)->value('karma_minus'));
    }

    public function testTheAmnestyIsForWhoeverManagesTheSettings(): void
    {
        $moderator = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS, CorePermissions::USERS_BAN_MANAGE, CorePermissions::USERS_BAN_PERMANENT], level: 60);
        $ban = $this->ban(FunctionalUserFactory::create(), BanKind::FORUM, time() + 3600);

        $panel = (string) $this->handleRequest('/admin/bans', cookies: $this->actingAs($moderator))->getContent();
        $response = $this->postForm('/admin/bans/amnesty', ['term' => '1'], $moderator);

        self::assertStringNotContainsString('href="/admin/bans/amnesty"', $panel);
        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertNotNull(Ban::query()->find($ban));
    }

    private function grant(int $term): Response
    {
        return $this->postForm('/admin/bans/amnesty', ['term' => (string) $term], FunctionalUserFactory::createSupervisor());
    }

    /**
     * A ban, and the karma it cost when there is a penalty: the system writes it at the moment the
     * ban was given, which is how deleting the ban finds it.
     */
    private function ban(User $user, BanKind $kind, int $until, int $penalty = 0): int
    {
        $given = time() - 60;

        if ($penalty > 0) {
            Fixture::insert('karma_users', ['karma_user' => $user->id, 'user_id' => 0, 'points' => $penalty, 'type' => 0, 'time' => $given]);
        }

        return Fixture::insert('cms_ban_users', [
            'user_id'    => $user->id,
            'ban_time'   => $until,
            'ban_while'  => $given,
            'ban_type'   => $kind->value,
            'ban_who'    => 'admin',
            'ban_reason' => 'Test',
        ]);
    }

    private function banOf(int $id): Ban
    {
        return Ban::query()->findOrFail($id);
    }
}
