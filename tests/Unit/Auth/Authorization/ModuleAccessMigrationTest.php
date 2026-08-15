<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Authorization;

use Johncms\Auth\Authorization\ModuleAccessMigration;
use Johncms\Auth\Authorization\Role;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeRoleRepository;

final class ModuleAccessMigrationTest extends TestCase
{
    private FakeRoleRepository $roles;

    private ModuleAccessMigration $migration;

    protected function setUp(): void
    {
        $this->roles = new FakeRoleRepository();
        $this->roles->add('guest', isGuest: true);
        $this->roles->add('user', isDefault: true);
        $this->roles->add('admin', level: 70);
        $this->migration = new ModuleAccessMigration($this->roles);
    }

    public function testAForumOpenToEverybodyLeavesBothRolesReading(): void
    {
        $this->migration->apply(['mod_forum' => 2]);

        self::assertSame(['forum.view'], $this->permissionsOf('guest'));
        self::assertEqualsCanonicalizing(['forum.view', 'forum.post'], $this->permissionsOf('user'));
    }

    /**
     * "For registered visitors only" is the guest role without forum.view — and the migration has
     * to take it away, because the defaults hand it to every fresh installation.
     */
    public function testAForumForRegisteredVisitorsTakesReadingFromTheGuest(): void
    {
        $this->grant('guest', ['forum.view']);

        $changes = $this->migration->apply(['mod_forum' => 1]);

        self::assertSame([], $this->permissionsOf('guest'));
        self::assertSame(['forum.view'], $changes['guest']['revoked']);
    }

    /**
     * The read-only forum is the one setting that was two answers in one number: everybody reads,
     * only the staff write.
     */
    public function testAReadOnlyForumKeepsReadingAndTakesWriting(): void
    {
        $this->grant('user', ['forum.view', 'forum.post']);

        $this->migration->apply(['mod_forum' => 3]);

        self::assertSame(['forum.view'], $this->permissionsOf('guest'));
        self::assertSame(['forum.view'], $this->permissionsOf('user'));
    }

    /**
     * A closed forum was still open to the administrator, which was a comparison against the
     * number rather than a setting of its own.
     */
    public function testAClosedForumStaysOpenToTheAdministrator(): void
    {
        $this->grant('guest', ['forum.view']);
        $this->grant('user', ['forum.view', 'forum.post']);

        $this->migration->apply(['mod_forum' => 0]);

        self::assertSame([], $this->permissionsOf('guest'));
        self::assertSame([], $this->permissionsOf('user'));
        self::assertSame(['forum.view'], $this->permissionsOf('admin'));
    }

    /**
     * The staff exception only ever adds: with the forum open, the administrator keeps what the
     * defaults gave their role.
     */
    public function testTheStaffExceptionNeverTakesAnythingAway(): void
    {
        $this->grant('admin', ['forum.post']);

        $this->migration->apply(['mod_forum' => 2]);

        self::assertSame(['forum.post'], $this->permissionsOf('admin'));
    }

    public function testASettingTheSiteDoesNotHaveIsLeftAlone(): void
    {
        $this->grant('guest', ['forum.view']);

        self::assertSame([], $this->migration->apply([]));
        self::assertSame(['forum.view'], $this->permissionsOf('guest'));
    }

    public function testRunningItTwiceChangesNothingTheSecondTime(): void
    {
        $this->migration->apply(['mod_forum' => 1]);

        self::assertSame([], $this->migration->apply(['mod_forum' => 1]));
    }

    /**
     * @param list<string> $permissions
     */
    private function grant(string $slug, array $permissions): void
    {
        $this->roles->setPermissions($this->role($slug)->id, $permissions);
    }

    /**
     * @return list<string>
     */
    private function permissionsOf(string $slug): array
    {
        return $this->roles->permissionsFor([$this->role($slug)->id]);
    }

    private function role(string $slug): Role
    {
        $role = $this->roles->findBySlug($slug);
        self::assertNotNull($role);

        return $role;
    }
}
