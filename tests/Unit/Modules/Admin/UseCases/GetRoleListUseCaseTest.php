<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\UseCases;

use Johncms\Modules\Admin\Application\DTO\RoleListItemDTO;
use Johncms\Modules\Admin\Application\UseCases\GetRoleListUseCase;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\FakeRoleRepository;

final class GetRoleListUseCaseTest extends TestCase
{
    use BootsInMemoryDatabase;

    private FakeRoleRepository $roles;

    private GetRoleListUseCase $useCase;

    protected function setUp(): void
    {
        $this->bootDatabase();

        $this->roles = new FakeRoleRepository();
        $this->useCase = new GetRoleListUseCase($this->roles);
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testEachRowCarriesWhatTheRoleHoldsAndWhoHoldsIt(): void
    {
        $this->roles->add('editor', ['admin.access', 'forum.post'], level: 20);
        $this->roles->grantTo(7, ['editor']);
        $this->roles->grantTo(8, ['editor']);

        $row = $this->rowFor('editor', viewerLevel: 70);

        self::assertSame(2, $row->permissions);
        self::assertSame(2, $row->holders);
        self::assertTrue($row->manageable);
    }

    /**
     * A role standing above the visitor is still listed — hiding it would make the hierarchy
     * confusing — but it is not opened for editing.
     */
    public function testARoleAboveTheViewerIsListedButNotManageable(): void
    {
        $this->roles->add('supervisor', level: 90, isSystem: true);

        self::assertFalse($this->rowFor('supervisor', viewerLevel: 70)->manageable);
    }

    /**
     * The count of permissions says nothing about a role that is allowed everything by its level,
     * so the row carries the flag the screen prints instead of it.
     */
    public function testARoleAtSupervisorLevelIsMarkedAsAllowedEverything(): void
    {
        $this->roles->add('supervisor', level: 90, isSystem: true);
        $this->roles->add('editor', level: 20);

        self::assertTrue($this->rowFor('supervisor', viewerLevel: 90)->fullAccess);
        self::assertFalse($this->rowFor('editor', viewerLevel: 90)->fullAccess);
    }

    private function rowFor(string $slug, int $viewerLevel): RoleListItemDTO
    {
        foreach ($this->useCase->execute($viewerLevel) as $item) {
            if ($item->slug === $slug) {
                return $item;
            }
        }

        self::fail('No row for the ' . $slug . ' role.');
    }
}
