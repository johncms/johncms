<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Authorization;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Johncms\Auth\Authorization\AccessChecker;
use Johncms\Auth\Authorization\RoleLevels;
use Johncms\Auth\Authorization\SystemRole;
use Johncms\Auth\Authorization\Vote;
use Johncms\Auth\Authorization\Voters\BanVoter;
use Johncms\Auth\Authorization\Voters\RolePermissionVoter;
use Johncms\Auth\Authorization\Voters\SuperAdminVoter;
use Johncms\Auth\CurrentUser;
use Johncms\Auth\Authentication\AuthenticatorChain;
use Johncms\Auth\Authorization\PermissionResolver;
use Johncms\Auth\Identity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\FakeRoleRepository;
use Tests\Support\FakeUserRepository;
use Tests\Support\IdentityFactory;

final class VotersTest extends TestCase
{
    use BootsInMemoryDatabase;

    protected function setUp(): void
    {
        $this->bootDatabase();
        $this->createBansTable();
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testRolePermissionsAllowAndNothingElse(): void
    {
        $voter = new RolePermissionVoter();
        $identity = IdentityFactory::withPermissions(['forum.*']);

        self::assertSame(Vote::Allow, $voter->vote($identity, 'forum.topic.delete', null));
        // Abstain, not Deny: another voter may still allow it, and only a Deny is final.
        self::assertSame(Vote::Abstain, $voter->vote($identity, 'news.article.edit', null));
    }

    /**
     * With permissions editable from the admin panel, a wrong click would otherwise be enough to
     * lock a site out of its own settings for good.
     */
    public function testSupervisorLevelAllowsEverything(): void
    {
        $roles = new FakeRoleRepository();
        $voter = new SuperAdminVoter(new RoleLevels($roles));

        $supervisor = IdentityFactory::withRoles([SystemRole::Supervisor->value]);

        self::assertSame(Vote::Allow, $voter->vote($supervisor, 'anything.at.all', null));
    }

    public function testLesserRolesGetNothingFromIt(): void
    {
        $voter = new SuperAdminVoter(new RoleLevels(new FakeRoleRepository()));

        self::assertSame(
            Vote::Abstain,
            $voter->vote(IdentityFactory::withRoles([SystemRole::Admin->value]), 'anything.at.all', null)
        );
        self::assertSame(Vote::Abstain, $voter->vote(Identity::guest(), 'anything.at.all', null));
    }

    /**
     * A role the site added itself reaches supervisor level through its own number rather than
     * through its slug.
     */
    public function testASiteDefinedRoleCanReachSupervisorLevel(): void
    {
        $roles = new FakeRoleRepository();
        $roles->add('owner', level: SystemRole::SUPERVISOR_LEVEL);

        $voter = new SuperAdminVoter(new RoleLevels($roles));

        self::assertSame(Vote::Allow, $voter->vote(IdentityFactory::withRoles(['owner']), 'anything', null));
    }

    /**
     * What a guest may do stays open — a banned member would otherwise see less of the site than
     * somebody who never signed in. Everything above that is refused.
     */
    public function testAFullBanLeavesWhatAGuestMayDo(): void
    {
        $this->ban(userId: 7, until: time() + 3600);
        $voter = $this->banVoter(guestPermissions: ['forum.view']);

        self::assertSame(Vote::Deny, $voter->vote(IdentityFactory::user(id: 7), 'forum.post', null));
        self::assertSame(Vote::Abstain, $voter->vote(IdentityFactory::user(id: 7), 'forum.view', null));
        self::assertSame(Vote::Abstain, $voter->vote(IdentityFactory::user(id: 8), 'forum.post', null));
    }

    /**
     * A ban of the forum takes the forum away, its moderation included, and leaves what a guest may
     * do there and every other section.
     */
    public function testABanOfASectionTakesThePermissionsOfThatSection(): void
    {
        $this->ban(userId: 7, until: time() + 3600, kind: 11);
        $voter = $this->banVoter(guestPermissions: ['forum.view']);
        $moderator = IdentityFactory::user(id: 7);

        self::assertSame(Vote::Deny, $voter->vote($moderator, 'forum.post', null));
        self::assertSame(Vote::Deny, $voter->vote($moderator, 'forum.topic.moderate', null));
        self::assertSame(Vote::Abstain, $voter->vote($moderator, 'forum.view', null));
        self::assertSame(Vote::Abstain, $voter->vote($moderator, 'guestbook.entry.manage', null));
    }

    /**
     * The private messages ask for no permission: their ban is the business of the mail alone.
     */
    public function testABanOfThePrivateMessagesTakesNoPermission(): void
    {
        $this->ban(userId: 7, until: time() + 3600, kind: 3);

        self::assertSame(Vote::Abstain, $this->banVoter()->vote(IdentityFactory::user(id: 7), 'forum.post', null));
    }

    public function testAnExpiredBanRefusesNothing(): void
    {
        $this->ban(userId: 7, until: time() - 10);

        self::assertSame(Vote::Abstain, $this->banVoter()->vote(IdentityFactory::user(id: 7), 'forum.post', null));
    }

    /**
     * The point of a Deny: it has to outrank what the roles say, so a banned administrator
     * administers nothing.
     */
    public function testAFullBanOutranksTheRolesOfAnAdministrator(): void
    {
        $this->ban(userId: 7, until: time() + 3600);

        $administrator = IdentityFactory::user(id: 7, permissions: ['*'], roles: [SystemRole::Admin->value]);

        self::assertFalse($this->checker()->allowsFor($administrator, 'admin.access'));
    }

    /**
     * The way back into a site that SuperAdminVoter guarantees holds under a ban as well.
     */
    public function testTheSupervisorIsNotLockedOutByABan(): void
    {
        $this->ban(userId: 7, until: time() + 3600);

        $supervisor = new Identity(userId: 7, roles: [SystemRole::Supervisor->value]);

        self::assertTrue($this->checker()->allowsFor($supervisor, 'admin.access'));
    }

    /**
     * @param list<string> $guestPermissions
     */
    private function banVoter(array $guestPermissions = []): BanVoter
    {
        $roles = new FakeRoleRepository();
        $roles->add(SystemRole::Guest->value, $guestPermissions, level: 0, isGuest: true);

        return new BanVoter(new PermissionResolver($roles), new RoleLevels($roles));
    }

    private function checker(): AccessChecker
    {
        return new AccessChecker(
            new CurrentUser(
                new AuthenticatorChain([]),
                new PermissionResolver(new FakeRoleRepository()),
                new RequestStack(),
                new FakeUserRepository()
            ),
            [new RolePermissionVoter(), new SuperAdminVoter(new RoleLevels(new FakeRoleRepository())), $this->banVoter()]
        );
    }

    private function ban(int $userId, int $until, int $kind = 1): void
    {
        Capsule::table('cms_ban_users')->insert(['user_id' => $userId, 'ban_time' => $until, 'ban_type' => $kind]);
    }

    private function createBansTable(): void
    {
        Capsule::schema()->create(
            'cms_ban_users',
            static function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('user_id')->unsigned();
                $table->integer('ban_time')->unsigned()->default(0);
                $table->integer('ban_type')->unsigned()->default(0);
            }
        );
    }
}
