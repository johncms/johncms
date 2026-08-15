<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Authorization;

use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Authorization\DefaultPermissions;
use Johncms\Auth\Authorization\PermissionRegistry;
use Johncms\Auth\Authorization\Role;
use Johncms\Auth\Authorization\RoleSeeder;
use Johncms\Auth\Authorization\SystemRole;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentRoleRepository;
use Johncms\Auth\Schema\AuthSchema;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;

final class RoleSeederTest extends TestCase
{
    use BootsInMemoryDatabase;

    private EloquentRoleRepository $roles;

    private RoleSeeder $seeder;

    protected function setUp(): void
    {
        $this->bootDatabase();
        // The seeder names the roles through the gettext helpers, which nothing has registered
        // in an isolated unit test.
        TranslatorFunctions::register(new Translator());
        AuthSchema::create(Capsule::schema());

        $this->roles = new EloquentRoleRepository();
        $this->seeder = new RoleSeeder($this->roles, $this->defaults());
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testEveryBuiltInRoleIsCreated(): void
    {
        $created = $this->seeder->seed();

        self::assertCount(count(SystemRole::cases()), $created);

        foreach (SystemRole::cases() as $systemRole) {
            $role = $this->roles->findBySlug($systemRole->value);

            self::assertNotNull($role, $systemRole->value . ' should exist');
            self::assertTrue($role->is_system);
            self::assertSame($systemRole->level(), $role->level);
        }
    }

    /**
     * The table stays small because the role everybody has is applied without a row per account.
     */
    public function testOnlyTheUserRoleIsDefault(): void
    {
        $this->seeder->seed();

        $defaults = $this->roles->defaults();

        self::assertCount(1, $defaults);
        self::assertSame(SystemRole::User->value, $defaults->first()?->slug);
    }

    public function testExactlyOneRoleStandsForAnonymousVisitors(): void
    {
        $this->seeder->seed();

        self::assertSame(SystemRole::Guest->value, $this->roles->guestRole()?->slug);
        self::assertSame(1, Role::query()->where('is_guest', '=', true)->count());
    }

    public function testTheAdministratorRoleCanEnterTheAdminPanel(): void
    {
        $this->seeder->seed();

        $admin = $this->roles->findBySlug(SystemRole::Admin->value);

        self::assertNotNull($admin);
        self::assertContains(
            CorePermissions::ADMIN_ACCESS,
            $this->roles->permissionsFor([$admin->id])
        );
    }

    /**
     * A fresh installation and an upgraded one must end up with the same matrix, so both read the
     * same table rather than each carrying its own idea of it.
     */
    public function testARoleIsCreatedWithTheDefaultsOfItsSlug(): void
    {
        $this->seeder->seed();

        foreach (SystemRole::cases() as $systemRole) {
            $role = $this->roles->findBySlug($systemRole->value);
            self::assertNotNull($role);

            self::assertEqualsCanonicalizing(
                $this->defaults()->forRole($systemRole->value),
                $this->roles->permissionsFor([$role->id]),
                $systemRole->value . ' should carry its default permissions'
            );
        }
    }

    public function testRunningAgainCreatesNothing(): void
    {
        $this->seeder->seed();

        self::assertSame([], $this->seeder->seed());
        self::assertSame(count(SystemRole::cases()), Role::query()->count());
    }

    /**
     * Re-running must never undo what a site configured; the only thing it fixes is what is
     * missing.
     */
    public function testRunningAgainKeepsTheConfiguredPermissions(): void
    {
        $this->seeder->seed();
        $admin = $this->roles->findBySlug(SystemRole::Admin->value);
        self::assertNotNull($admin);

        $this->roles->setPermissions($admin->id, ['forum.topic.delete']);
        $this->seeder->seed();

        self::assertSame(['forum.topic.delete'], $this->roles->permissionsFor([$admin->id]));
    }

    public function testAMissingRoleIsAddedBackWithoutTouchingTheOthers(): void
    {
        $this->seeder->seed();
        Role::query()->where('slug', '=', SystemRole::LibraryModerator->value)->delete();

        self::assertSame([SystemRole::LibraryModerator->value], $this->seeder->seed());
    }

    /**
     * The matrix as the running site assembles it: from the permissions the core and the modules
     * declare, not from a copy kept in the test.
     */
    private function defaults(): DefaultPermissions
    {
        return new DefaultPermissions(new PermissionRegistry([new CorePermissions(), new ForumPermissions()]));
    }
}
