<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\UseCases;

use Johncms\Auth\Authorization\PermissionDefinition;
use Johncms\Auth\Authorization\PermissionRegistry;
use Johncms\Auth\Authorization\SystemRole;
use Johncms\Modules\Admin\Application\DTO\RoleFormDTO;
use Johncms\Modules\Admin\Application\Exceptions\RoleNotFoundException;
use Johncms\Modules\Admin\Application\Exceptions\RoleSlugTakenException;
use Johncms\Modules\Admin\Application\UseCases\SaveRoleUseCase;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\FakeRoleRepository;

final class SaveRoleUseCaseTest extends TestCase
{
    use BootsInMemoryDatabase;

    private FakeRoleRepository $roles;

    private SaveRoleUseCase $useCase;

    protected function setUp(): void
    {
        $this->bootDatabase();

        $this->roles = new FakeRoleRepository();
        $this->useCase = new SaveRoleUseCase(
            $this->roles,
            new PermissionRegistry(definitions: [
                new PermissionDefinition('admin.access', 'admin', 'Access the admin panel'),
                new PermissionDefinition('admin.settings.manage', 'admin', 'Change system settings'),
            ])
        );
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testANewRoleIsCreatedWithWhatWasTicked(): void
    {
        $id = $this->useCase->execute($this->form(slug: 'news-editor', permissions: ['admin.access']));

        $role = $this->roles->findById($id);

        self::assertNotNull($role);
        self::assertSame('news-editor', $role->slug);
        self::assertSame(['admin.access'], $this->roles->permissionsFor([$id]));
    }

    public function testASlugAlreadyInUseIsRefused(): void
    {
        $this->roles->add('news-editor');

        $this->expectException(RoleSlugTakenException::class);

        $this->useCase->execute($this->form(slug: 'news-editor'));
    }

    /**
     * A module switched off takes its permissions out of the editor. Storing only what the form
     * showed would quietly revoke them, and switching the module back on would find the role
     * empty.
     */
    public function testPermissionsTheCatalogueDoesNotDeclareSurviveASave(): void
    {
        $role = $this->roles->add('news-editor', ['forum.*', 'admin.access']);

        $this->useCase->execute($this->form(id: $role->id, permissions: ['admin.settings.manage']));

        self::assertSame(['forum.*', 'admin.settings.manage'], $this->roles->permissionsFor([$role->id]));
    }

    /**
     * The form is not to be trusted: a request made by hand must not be able to invent a
     * permission nobody declares.
     */
    public function testAKeyNobodyDeclaresIsNotStored(): void
    {
        $role = $this->roles->add('news-editor');

        $this->useCase->execute($this->form(id: $role->id, permissions: ['admin.access', 'made.up.key']));

        self::assertSame(['admin.access'], $this->roles->permissionsFor([$role->id]));
    }

    public function testABuiltInRoleKeepsItsNameAndItsLevel(): void
    {
        $role = $this->roles->add('admin', level: 70, isSystem: true);

        $this->useCase->execute(
            $this->form(id: $role->id, name: 'Renamed', level: 5, permissions: ['admin.access'])
        );

        $stored = $this->roles->findById($role->id);

        self::assertNotNull($stored);
        self::assertSame('admin', $stored->name);
        self::assertSame(70, $stored->level);
        self::assertSame(['admin.access'], $this->roles->permissionsFor([$role->id]));
    }

    /**
     * A role at supervisor level is allowed everything by SuperAdminVoter, so the editor shows it
     * no matrix. A save must not be read as "tick nothing" and wipe what it happens to carry.
     */
    public function testASupervisorLevelRoleKeepsItsPermissionsWhenTheMatrixIsNotShown(): void
    {
        $role = $this->roles->add('owner', ['admin.access'], level: SystemRole::SUPERVISOR_LEVEL);

        $this->useCase->execute($this->form(id: $role->id, level: SystemRole::SUPERVISOR_LEVEL));

        self::assertSame(['admin.access'], $this->roles->permissionsFor([$role->id]));
    }

    public function testARoleThatIsGoneIsReported(): void
    {
        $this->expectException(RoleNotFoundException::class);

        $this->useCase->execute($this->form(id: 404));
    }

    /**
     * @param list<string> $permissions
     */
    private function form(
        ?int $id = null,
        string $slug = 'news-editor',
        string $name = 'News editor',
        int $level = 20,
        array $permissions = [],
    ): RoleFormDTO {
        return new RoleFormDTO($id, $slug, $name, $level, $permissions);
    }
}
