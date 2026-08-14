<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Authorization;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Johncms\Auth\Authorization\LegacyRightsMigration;
use Johncms\Auth\Authorization\RightsMirror;
use Johncms\Auth\Authorization\RoleSeeder;
use Johncms\Auth\Authorization\SystemRole;
use Johncms\Auth\Authorization\UserRole;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentRoleRepository;
use Johncms\Auth\Schema\AuthSchema;
use Johncms\Users\User;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;

final class LegacyRightsMigrationTest extends TestCase
{
    use BootsInMemoryDatabase;

    private EloquentRoleRepository $roles;

    private LegacyRightsMigration $migration;

    private RightsMirror $mirror;

    protected function setUp(): void
    {
        $this->bootDatabase();
        // The seeder names the roles through the gettext helpers, which nothing has registered
        // in an isolated unit test.
        TranslatorFunctions::register(new Translator());
        AuthSchema::create(Capsule::schema());
        $this->createUsersTable();

        $this->roles = new EloquentRoleRepository();
        (new RoleSeeder($this->roles))->seed();

        $this->migration = new LegacyRightsMigration($this->roles);
        $this->mirror = new RightsMirror($this->roles);
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testEachAccessLevelBecomesItsRole(): void
    {
        $admin = $this->createUser(rights: 7);
        $forumModerator = $this->createUser(rights: 3);

        $this->migration->migrate();

        self::assertSame([SystemRole::Admin->value], $this->slugsOf($admin));
        self::assertSame([SystemRole::ForumModerator->value], $this->slugsOf($forumModerator));
    }

    /**
     * The role everybody has is applied without a row, which is what keeps the table small on a
     * site with a hundred thousand accounts.
     */
    public function testOrdinaryAccountsGetNoRow(): void
    {
        $this->createUser(rights: 0);
        $this->createUser(rights: 0);

        $report = $this->migration->migrate();

        self::assertSame(0, $report->granted);
        self::assertSame(0, UserRole::query()->count());
    }

    /**
     * Checks like `rights >= 1` gave the undocumented values meaning, so accounts holding them
     * cannot be dropped silently.
     */
    public function testUndocumentedLevelsAreMappedDownAndReported(): void
    {
        $one = $this->createUser(rights: 1);
        $eight = $this->createUser(rights: 8);

        $report = $this->migration->migrate();

        self::assertTrue($report->hasUnrecognised());
        self::assertSame([1 => [$one->id], 8 => [$eight->id]], $report->unrecognised);

        // Never more than the account had: 8 lands on the administrator role, 1 on the plain user.
        self::assertSame([SystemRole::Admin->value], $this->slugsOf($eight));
        self::assertSame([], $this->slugsOf($one));
    }

    public function testRunningAgainLeavesArrangedAccountsAlone(): void
    {
        $user = $this->createUser(rights: 3);
        $this->migration->migrate();

        // Somebody rearranges it by hand afterwards.
        $moderator = $this->roles->findBySlug(SystemRole::ForumModerator->value);
        $superModerator = $this->roles->findBySlug(SystemRole::SuperModerator->value);
        self::assertNotNull($moderator);
        self::assertNotNull($superModerator);
        $this->roles->revoke($user->id, $moderator->id);
        $this->roles->grant($user->id, $superModerator->id, null, time());

        $report = $this->migration->migrate();

        self::assertSame(1, $report->skipped);
        self::assertSame([SystemRole::SuperModerator->value], $this->slugsOf($user));
    }

    public function testResettingRedoesThemAnyway(): void
    {
        $user = $this->createUser(rights: 3);
        $this->migration->migrate();

        $superModerator = $this->roles->findBySlug(SystemRole::SuperModerator->value);
        self::assertNotNull($superModerator);
        $this->roles->grant($user->id, $superModerator->id, null, time());

        $this->migration->migrate(reset: true);

        self::assertContains(SystemRole::ForumModerator->value, $this->slugsOf($user));
    }

    public function testTheSnapshotHoldsWhatTheColumnSaid(): void
    {
        $admin = $this->createUser(rights: 7);
        $odd = $this->createUser(rights: 8);
        $this->createUser(rights: 0);

        self::assertSame([$admin->id => 7, $odd->id => 8], $this->migration->snapshot());
    }

    /**
     * The old number has to go on answering while three hundred checks are still comparing
     * against it.
     */
    public function testTheNumberIsRecomputedFromTheRoles(): void
    {
        $user = $this->createUser(rights: 0);
        $admin = $this->roles->findBySlug(SystemRole::Admin->value);
        self::assertNotNull($admin);

        $this->roles->grant($user->id, $admin->id, null, time());

        self::assertSame(7, $this->mirror->sync($user->id));
        self::assertSame(7, User::query()->findOrFail($user->id)->rights);
    }

    public function testTheHighestRoleWins(): void
    {
        $user = $this->createUser(rights: 0);

        foreach ([SystemRole::ForumModerator, SystemRole::Admin] as $slug) {
            $role = $this->roles->findBySlug($slug->value);
            self::assertNotNull($role);
            $this->roles->grant($user->id, $role->id, null, time());
        }

        self::assertSame(7, $this->mirror->sync($user->id));
    }

    public function testLosingEveryRoleBringsTheNumberBackToZero(): void
    {
        $user = $this->createUser(rights: 7);
        $this->migration->migrate();

        $admin = $this->roles->findBySlug(SystemRole::Admin->value);
        self::assertNotNull($admin);
        $this->roles->revoke($user->id, $admin->id);

        self::assertSame(0, $this->mirror->sync($user->id));
    }

    /**
     * A role granted until a date stops counting on its own, and the number follows.
     */
    public function testAnExpiredRoleStopsCounting(): void
    {
        $user = $this->createUser(rights: 0);
        $moderator = $this->roles->findBySlug(SystemRole::ForumModerator->value);
        self::assertNotNull($moderator);

        $now = time();
        $this->roles->grant($user->id, $moderator->id, null, $now, $now + 3600);

        self::assertSame(3, $this->mirror->rightsFor($user->id, $now));
        self::assertSame(0, $this->mirror->rightsFor($user->id, $now + 7200));
    }

    /**
     * @return list<string>
     */
    private function slugsOf(User $user): array
    {
        return $this->roles->grantedTo($user->id, time())->pluck('slug')->values()->all();
    }

    private function createUser(int $rights): User
    {
        $user = new User();
        $user->fill(['name' => 'u' . $rights . '-' . uniqid(), 'rights' => $rights]);
        $user->save();

        return $user;
    }

    private function createUsersTable(): void
    {
        Capsule::schema()->create(
            'users',
            static function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name')->default('');
                $table->integer('rights')->default(0);
            }
        );
    }
}
