<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\UseCases;

use Johncms\Auth\Authorization\PermissionDefinition;
use Johncms\Auth\Authorization\PermissionRegistry;
use Johncms\Modules\Admin\Application\Exceptions\RoleNotFoundException;
use Johncms\Modules\Admin\Application\UseCases\GetRoleEditorUseCase;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\FakeRoleRepository;

final class GetRoleEditorUseCaseTest extends TestCase
{
    use BootsInMemoryDatabase;

    private FakeRoleRepository $roles;

    private GetRoleEditorUseCase $useCase;

    protected function setUp(): void
    {
        $this->bootDatabase();

        $this->roles = new FakeRoleRepository();
        $this->useCase = new GetRoleEditorUseCase(
            $this->roles,
            new PermissionRegistry(definitions: [
                new PermissionDefinition('admin.access', 'admin', 'Access the admin panel', 'Admin panel'),
                new PermissionDefinition('forum.post', 'forum', 'Write posts', 'Forum'),
            ])
        );
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testTheMatrixIsGroupedAndMarksWhatTheRoleCarries(): void
    {
        $role = $this->roles->add('editor', ['forum.post']);

        $editor = $this->useCase->execute($role->id);

        self::assertCount(2, $editor->groups);
        self::assertSame('Admin panel', $editor->groups[0]->label);
        self::assertFalse($editor->groups[0]->items[0]->granted);
        self::assertSame('Forum', $editor->groups[1]->label);
        self::assertTrue($editor->groups[1]->items[0]->granted);
    }

    /**
     * A module switched off, or a pattern such as `forum.*`: the editor cannot show a checkbox for
     * it, so it says so rather than pretending the role does not carry it.
     */
    public function testPermissionsNobodyDeclaresAreListedSeparately(): void
    {
        $role = $this->roles->add('editor', ['forum.*', 'admin.access']);

        $editor = $this->useCase->execute($role->id);

        self::assertSame(['forum.*'], $editor->undeclared);
    }

    public function testTheEditorOfANewRoleCarriesNothing(): void
    {
        $editor = $this->useCase->execute(null);

        self::assertNull($editor->role);
        self::assertSame([], $editor->undeclared);
        self::assertFalse($editor->groups[0]->items[0]->granted);
    }

    public function testARoleThatIsGoneIsReported(): void
    {
        $this->expectException(RoleNotFoundException::class);

        $this->useCase->execute(404);
    }
}
