<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\UseCases;

use Johncms\Modules\Admin\Application\Exceptions\RoleNotFoundException;
use Johncms\Modules\Admin\Application\Exceptions\SystemRoleNotDeletableException;
use Johncms\Modules\Admin\Application\UseCases\DeleteRoleUseCase;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\FakeRoleRepository;

final class DeleteRoleUseCaseTest extends TestCase
{
    use BootsInMemoryDatabase;

    private FakeRoleRepository $roles;

    private DeleteRoleUseCase $useCase;

    protected function setUp(): void
    {
        $this->bootDatabase();

        $this->roles = new FakeRoleRepository();
        $this->useCase = new DeleteRoleUseCase($this->roles);
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testARoleGoesAwayTogetherWithWhatItGranted(): void
    {
        $role = $this->roles->add('news-editor', ['admin.access']);
        $this->roles->grantTo(7, ['news-editor']);

        $this->useCase->execute($role->id);

        self::assertNull($this->roles->findById($role->id));
        self::assertCount(0, $this->roles->grantedTo(7, time()));
    }

    /**
     * Code refers to the built-in roles by slug: an installation without the guest role, the
     * default role or the supervisor would have no way back in.
     */
    public function testABuiltInRoleCannotBeDeleted(): void
    {
        $role = $this->roles->add('supervisor', level: 90, isSystem: true);

        $this->expectException(SystemRoleNotDeletableException::class);

        $this->useCase->execute($role->id);
    }

    public function testARoleThatIsGoneIsReported(): void
    {
        $this->expectException(RoleNotFoundException::class);

        $this->useCase->execute(404);
    }
}
