<?php

declare(strict_types=1);

namespace Tests\Functional\Profile;

use Johncms\Auth\Authorization\SystemRole;
use Johncms\Config\ConfigRepository;
use Johncms\Users\Ban;
use Johncms\Users\Karma;
use Johncms\Users\User;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * Giving, lifting and deleting the bans of an account from its profile: who may do which, for how
 * long, and what a ban costs in karma.
 */
final class BanManagementTest extends FunctionalTestCase
{
    private const int DAY = 86400;

    private const int MONTH = 2592000;

    /** "Until cancelled" is ten years. */
    private const int TILL_CANCEL = 315360000;

    /** The units of the form. */
    private const int DAYS = 3;

    private const int UNTIL_CANCELLED = 4;

    public function testAModeratorOfTheForumBansFromTheForumForADayAtMost(): void
    {
        $moderator = FunctionalUserFactory::create([], [SystemRole::ForumModerator->value]);
        $member = FunctionalUserFactory::create();

        $this->give($moderator, $member, kind: 11, amount: 3, unit: self::DAYS);

        $ban = $this->onlyBanOf($member);
        self::assertSame(11, $ban->ban_type);
        self::assertSame(self::DAY, $ban->ban_time - $ban->ban_while);
        self::assertSame($moderator->name, $ban->ban_who);
        self::assertSame('Flood', $ban->ban_reason);
    }

    public function testAModeratorOfTheForumDoesNotBanFromTheWholeSite(): void
    {
        $moderator = FunctionalUserFactory::create([], [SystemRole::ForumModerator->value]);
        $member = FunctionalUserFactory::create();

        $this->give($moderator, $member, kind: 1);

        self::assertSame([], $this->bansOf($member));
    }

    public function testABanGoesDownwardsOnly(): void
    {
        $moderator = FunctionalUserFactory::create([], [SystemRole::ForumModerator->value]);
        $peer = FunctionalUserFactory::create([], [SystemRole::ForumModerator->value]);

        self::assertSame(403, $this->give($moderator, $peer, kind: 11));
        self::assertSame([], $this->bansOf($peer));
    }

    public function testASuperModeratorBansForAMonthAtMost(): void
    {
        $moderator = FunctionalUserFactory::create([], [SystemRole::SuperModerator->value]);
        $member = FunctionalUserFactory::create();

        $this->give($moderator, $member, kind: 1, unit: self::UNTIL_CANCELLED);

        $ban = $this->onlyBanOf($member);
        self::assertSame(self::MONTH, $ban->ban_time - $ban->ban_while);
    }

    public function testAnAdministratorBansUntilCancelled(): void
    {
        $admin = FunctionalUserFactory::create([], [SystemRole::Admin->value]);
        $member = FunctionalUserFactory::create();

        $this->give($admin, $member, kind: 1, unit: self::UNTIL_CANCELLED);

        $ban = $this->onlyBanOf($member);
        self::assertSame(self::TILL_CANCEL, $ban->ban_time - $ban->ban_while);
    }

    public function testAKindOfBanInForceIsNotGivenTwice(): void
    {
        $moderator = FunctionalUserFactory::create([], [SystemRole::ForumModerator->value]);
        $member = FunctionalUserFactory::create();
        $this->insertBan($member, kind: 11);

        $this->give($moderator, $member, kind: 11);

        self::assertCount(1, $this->bansOf($member));
    }

    public function testABanCostsKarmaWhileKarmaIsOn(): void
    {
        $admin = FunctionalUserFactory::create([], [SystemRole::Admin->value]);
        $member = FunctionalUserFactory::create();

        $this->withKarma(fn () => $this->give($admin, $member, kind: 1));

        $ban = $this->onlyBanOf($member);
        $penalty = $this->systemPenaltiesOf($member);
        self::assertCount(1, $penalty);
        self::assertSame(10, $penalty[0]->points);
        self::assertSame($ban->ban_while, $penalty[0]->time);
        self::assertSame(10, $this->karmaMinusOf($member));
    }

    public function testLiftingABanEndsItAndKeepsItInTheHistory(): void
    {
        $admin = FunctionalUserFactory::create([], [SystemRole::Admin->value]);
        $member = FunctionalUserFactory::create();
        $ban = $this->insertBan($member, kind: 1);

        $this->post($admin, '/profile/' . $member->id . '/bans/' . $ban . '/cancel');

        self::assertFalse($this->onlyBanOf($member)->is_active);
    }

    public function testNobodyLiftsABanOfTheirOwn(): void
    {
        $admin = FunctionalUserFactory::create([], [SystemRole::Admin->value]);
        $ban = $this->insertBan($admin, kind: 11);

        $this->post($admin, '/profile/' . $admin->id . '/bans/' . $ban . '/cancel');

        self::assertTrue($this->onlyBanOf($admin)->is_active);
    }

    /**
     * A ban is lifted the way it is given, downwards only: an administrator does not end the ban
     * the supervisor gave another administrator.
     */
    public function testABanIsLiftedOnlyBySomebodyStandingAbove(): void
    {
        $admin = FunctionalUserFactory::create([], [SystemRole::Admin->value]);
        $peer = FunctionalUserFactory::create([], [SystemRole::Admin->value]);
        $ban = $this->insertBan($peer, kind: 1);

        self::assertSame(403, $this->post($admin, '/profile/' . $peer->id . '/bans/' . $ban . '/cancel'));
        self::assertTrue($this->onlyBanOf($peer)->is_active);

        $this->post(FunctionalUserFactory::createSupervisor(), '/profile/' . $peer->id . '/bans/' . $ban . '/cancel');

        self::assertFalse($this->onlyBanOf($peer)->is_active);
    }

    public function testOnlyTheSupervisorDeletesTheRecordOfABan(): void
    {
        $admin = FunctionalUserFactory::create([], [SystemRole::Admin->value]);
        $member = FunctionalUserFactory::create();
        $ban = $this->insertBan($member, kind: 1);

        self::assertSame(403, $this->post($admin, '/profile/' . $member->id . '/bans/' . $ban . '/delete'));
        self::assertCount(1, $this->bansOf($member));
    }

    public function testDeletingABanTakesBackItsKarma(): void
    {
        $supervisor = FunctionalUserFactory::createSupervisor();
        $member = FunctionalUserFactory::create();

        $this->withKarma(function () use ($supervisor, $member): void {
            $this->give($supervisor, $member, kind: 1);
            $this->post($supervisor, '/profile/' . $member->id . '/bans/' . $this->onlyBanOf($member)->id . '/delete');
        });

        self::assertSame([], $this->bansOf($member));
        self::assertSame([], $this->systemPenaltiesOf($member));
        self::assertSame(0, $this->karmaMinusOf($member));
    }

    /**
     * A ban given while karma was off cost nothing, and the minuses the account has are what other
     * members voted: deleting the ban leaves them.
     */
    public function testDeletingABanThatCostNoKarmaLeavesTheVotesOfTheMembers(): void
    {
        $supervisor = FunctionalUserFactory::createSupervisor();
        $member = FunctionalUserFactory::create(['karma_minus' => 10]);
        $ban = $this->insertBan($member, kind: 1);

        $this->withKarma(fn () => $this->post($supervisor, '/profile/' . $member->id . '/bans/' . $ban . '/delete'));

        self::assertSame([], $this->bansOf($member));
        self::assertSame(10, $this->karmaMinusOf($member));
    }

    /**
     * Pinned as it is: clearing the history deletes the bans still in force as well.
     */
    public function testClearingTheHistoryDeletesTheBansInForceToo(): void
    {
        $supervisor = FunctionalUserFactory::createSupervisor();
        $member = FunctionalUserFactory::create();
        $this->insertBan($member, kind: 1);
        $this->insertBan($member, kind: 11, until: time() - 60);

        $this->post($supervisor, '/profile/' . $member->id . '/bans/clear');

        self::assertSame([], $this->bansOf($member));
    }

    /**
     * Sends the form of a ban the way the profile page does, and answers the status of the reply.
     */
    private function give(User $by, User $to, int $kind, int $amount = 1, int $unit = self::DAYS): int
    {
        return $this->post($by, '/profile/' . $to->id . '/bans/new', [
            'term'    => $kind,
            'timeval' => $amount,
            'time'    => $unit,
            'reason'  => 'Flood',
            'banref'  => 0,
        ]);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function post(User $by, string $path, array $parameters = []): int
    {
        return $this->handleRequest($path, 'POST', $this->withCsrfToken($parameters), cookies: $this->actingAs($by))
            ->getStatusCode();
    }

    private function insertBan(User $user, int $kind, ?int $until = null): int
    {
        return Fixture::insert('cms_ban_users', [
            'user_id'    => $user->id,
            'ban_time'   => $until ?? time() + 3600,
            'ban_while'  => time() - 60,
            'ban_type'   => $kind,
            'ban_who'    => 'Supervisor',
            'ban_reason' => 'Spam',
        ]);
    }

    /**
     * @return list<Ban>
     */
    private function bansOf(User $user): array
    {
        return array_values(Ban::query()->where('user_id', '=', $user->id)->get()->all());
    }

    private function onlyBanOf(User $user): Ban
    {
        $bans = $this->bansOf($user);
        self::assertCount(1, $bans);

        return $bans[0];
    }

    /**
     * @return list<Karma> The entries the system wrote for the bans of the account.
     */
    private function systemPenaltiesOf(User $user): array
    {
        return array_values(
            Karma::query()->where('karma_user', '=', $user->id)->where('user_id', '=', 0)->get()->all()
        );
    }

    private function karmaMinusOf(User $user): int
    {
        return (int) User::query()->whereKey($user->id)->value('karma_minus');
    }

    /**
     * Runs the test with karma switched on at five points a vote, and puts the configuration back
     * afterwards: the container is built once per process and every later test reads the same values.
     *
     * @template T
     * @param callable(): T $test
     * @return T
     */
    private function withKarma(callable $test): mixed
    {
        $config = ConfigRepository::all();

        $johncms = $config['johncms'];
        $johncms['karma'] = array_replace((array) ($johncms['karma'] ?? []), ['on' => 1, 'karma_points' => 5]);
        ConfigRepository::init(array_replace($config, ['johncms' => $johncms]));

        try {
            return $test();
        } finally {
            ConfigRepository::init($config);
        }
    }
}
