<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Authorization\DefaultPermissions;
use Johncms\Auth\Authorization\DefaultPermissionsApplier;
use Johncms\Auth\Authorization\PermissionRegistry;
use Johncms\Auth\Authorization\Role;
use Johncms\Auth\Authorization\RoleSeeder;
use Johncms\Auth\Authorization\SystemRole;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentRoleRepository;
use Johncms\Updates\RolesDeclarationSynchronizer;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\RunsMigrations;

/**
 * The step an updated site takes to get the roles this version declares.
 *
 * A fresh installation gets them from the installer; an existing one has nothing that would give
 * it a role added after it was installed, which is what this command is for.
 */
final class RolesDeclarationSynchronizerTest extends TestCase
{
    use BootsInMemoryDatabase;
    use RunsMigrations;

    private EloquentRoleRepository $roles;

    protected function setUp(): void
    {
        $this->bootDatabase();
        $this->migrate('system', 'initial_auth_schema');

        // The seeder names the roles through the gettext helpers, which nothing has registered
        // in an isolated unit test.
        TranslatorFunctions::register(new Translator());

        $this->roles = new EloquentRoleRepository();
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    /**
     * The case this exists for: the tables are there and empty, which is where an upgrade from
     * 9.9 leaves a site.
     */
    public function testItCreatesTheBuiltInRolesOfASiteThatHasNone(): void
    {
        self::assertSame(0, Role::query()->count());

        $this->sync();

        self::assertSame(count(SystemRole::cases()), Role::query()->count());

        foreach (SystemRole::cases() as $systemRole) {
            self::assertNotNull($this->roles->findBySlug($systemRole->value), $systemRole->value . ' should exist');
        }
    }

    public function testARoleIsCreatedWithThePermissionsOfItsSlug(): void
    {
        $this->sync();

        $admin = $this->roles->findBySlug(SystemRole::Admin->value);
        self::assertNotNull($admin);

        self::assertEqualsCanonicalizing(
            $this->defaults()->forRole(SystemRole::Admin->value),
            $this->roles->permissionsFor([$admin->id])
        );
    }

    /**
     * A permission a release adds reaches the role it belongs to, without the site being
     * reinstalled.
     */
    public function testAPermissionMissingFromABuiltInRoleIsGranted(): void
    {
        $this->sync();

        $admin = $this->roles->findBySlug(SystemRole::Admin->value);
        self::assertNotNull($admin);

        $expected = $this->defaults()->forRole(SystemRole::Admin->value);
        $this->roles->setPermissions($admin->id, array_slice($expected, 1));

        $this->sync();

        self::assertEqualsCanonicalizing($expected, $this->roles->permissionsFor([$admin->id]));
    }

    public function testRunningItAgainChangesNothing(): void
    {
        $this->sync();
        $this->sync();

        self::assertSame(count(SystemRole::cases()), Role::query()->count());
    }

    private function sync(): void
    {
        $synchronizer = new RolesDeclarationSynchronizer(
            new RoleSeeder($this->roles, $this->defaults()),
            new DefaultPermissionsApplier($this->roles, $this->defaults())
        );
        $synchronizer->synchronize();
        $synchronizer->validate();
    }

    private function defaults(): DefaultPermissions
    {
        return new DefaultPermissions(new PermissionRegistry([new CorePermissions()]));
    }
}
