<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Authorization;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Auth\Authorization\RolePermissionPurger;
use Johncms\Auth\Authorization\SystemRole;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentRoleRepository;
use Johncms\Modules\Manifest\ModuleAutoload;
use Tests\Unit\Auth\Authorization\Blog\BlogPermissionProvider;
use Johncms\Modules\Manifest\ModuleManifest;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\RunsMigrations;

/**
 * Taking the permissions of a module out of the roles that hold them.
 *
 * The question this answers is which permissions are the module's, and the answer comes from the
 * module itself rather than from the shape of the key: `admin.settings.manage` belongs to the core,
 * not to whichever module happens to be called admin.
 */
final class RolePermissionPurgerTest extends TestCase
{
    use BootsInMemoryDatabase;
    use RunsMigrations;

    private EloquentRoleRepository $roles;

    private string $backupPath;

    protected function setUp(): void
    {
        $this->bootDatabase();
        TranslatorFunctions::register(new Translator());
        $this->migrate('system', 'initial_auth_schema');

        $this->roles = new EloquentRoleRepository();
        $this->backupPath = sys_get_temp_dir() . DS . 'johncms-purge-' . uniqid();
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->backupPath));
        $this->shutdownDatabase();
    }

    public function testOnlyThePermissionsTheModuleDeclaredAreTakenAway(): void
    {
        $role = $this->roles->create(SystemRole::Admin->value, 'Admin', 70, time());
        $this->roles->setPermissions($role->id, ['blog.manage', 'blog.comments.post', 'admin.settings.manage']);

        $removed = $this->purger()->purge($this->manifest());

        self::assertSame(2, $removed);
        self::assertSame(['admin.settings.manage'], $this->roles->permissionsFor([$role->id]));
    }

    public function testEveryRoleLosesThem(): void
    {
        $admin = $this->roles->create('admin', 'Admin', 70, time());
        $moderator = $this->roles->create('moderator', 'Moderator', 60, time());
        $this->roles->setPermissions($admin->id, ['blog.manage']);
        $this->roles->setPermissions($moderator->id, ['blog.manage', 'forum.view']);

        self::assertSame(2, $this->purger()->purge($this->manifest()));
        self::assertSame([], $this->roles->permissionsFor([$admin->id]));
        self::assertSame(['forum.view'], $this->roles->permissionsFor([$moderator->id]));
    }

    /**
     * Permissions are configuration somebody spent an evening on. A module removed by mistake
     * should not cost them that, so what is taken away is written out first.
     */
    public function testWhatIsRemovedIsBackedUp(): void
    {
        $role = $this->roles->create('admin', 'Admin', 70, time());
        $this->roles->setPermissions($role->id, ['blog.manage']);

        $this->purger()->purge($this->manifest());

        $files = (array) glob($this->backupPath . DS . 'permissions-vasya-blog-*.json');
        self::assertCount(1, $files);
        self::assertSame(
            ['admin' => ['blog.manage']],
            json_decode((string) file_get_contents((string) $files[0]), true)
        );
    }

    public function testAModuleThatDeclaredNothingChangesNothing(): void
    {
        $role = $this->roles->create('admin', 'Admin', 70, time());
        $this->roles->setPermissions($role->id, ['forum.view']);

        $purger = new RolePermissionPurger([], $this->roles, $this->backupPath);

        self::assertSame(0, $purger->purge($this->manifest()));
        self::assertSame(['forum.view'], $this->roles->permissionsFor([$role->id]));
        self::assertDirectoryDoesNotExist($this->backupPath);
    }

    /**
     * A provider belonging to another module is not this module's business, whatever its keys look
     * like.
     */
    public function testAProviderOutsideTheModuleIsLeftAlone(): void
    {
        $role = $this->roles->create('admin', 'Admin', 70, time());
        $this->roles->setPermissions($role->id, ['blog.manage', 'shop.manage']);

        $purger = new RolePermissionPurger(
            [new BlogPermissionProvider(), new ShopPermissionProvider()],
            $this->roles,
            $this->backupPath
        );

        self::assertSame(1, $purger->purge($this->manifest()));
        self::assertSame(['shop.manage'], $this->roles->permissionsFor([$role->id]));
    }

    private function purger(): RolePermissionPurger
    {
        return new RolePermissionPurger([new BlogPermissionProvider()], $this->roles, $this->backupPath);
    }

    private function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            key: 'vasya/blog',
            alias: 'blog',
            path: MODULES_PATH . 'vasya/blog',
            name: 'Blog',
            autoload: new ModuleAutoload(psr4: ['Tests\\Unit\\Auth\\Authorization\\Blog\\' => 'src']),
        );
    }
}
