<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\UseCases;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Johncms\Modules\Admin\Application\DTO\UserRoleRowDTO;
use Johncms\Modules\Admin\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Admin\Application\UseCases\GetUserRolesUseCase;
use Johncms\Modules\Admin\Domain\Enums\UserListSort;
use Johncms\Modules\Admin\Domain\Repository\UserListRepositoryInterface;
use Johncms\Users\User;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\FakeRoleRepository;

final class GetUserRolesUseCaseTest extends TestCase
{
    use BootsInMemoryDatabase;

    private FakeRoleRepository $roles;

    private GetUserRolesUseCase $useCase;

    protected function setUp(): void
    {
        $this->bootDatabase();
        // The built-in roles are named through the gettext helpers, which nothing has registered
        // in an isolated unit test.
        TranslatorFunctions::register(new Translator());

        $this->roles = new FakeRoleRepository();
        $this->roles->add('guest', level: 0, isGuest: true, isSystem: true);
        $this->roles->add('user', level: 10, isDefault: true, isSystem: true);
        $this->roles->add('editor', level: 20);
        $this->roles->add('supervisor', level: 90, isSystem: true);

        $this->useCase = new GetUserRolesUseCase($this->users(), $this->roles);
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    /**
     * Neither is ever handed out: the guest role is what somebody has instead of an account, and
     * the default one applies to everybody signed in without a row.
     */
    public function testTheGuestAndTheDefaultRoleAreNotOffered(): void
    {
        $context = $this->useCase->execute(7, viewerLevel: 90);

        self::assertSame(['editor', 'supervisor'], array_map(
            static fn (UserRoleRowDTO $row): string => $row->slug,
            $context->rows
        ));
        self::assertNotNull($context->defaultRole);
    }

    public function testAGrantThatRanOutIsShownAsExpired(): void
    {
        $editor = $this->roles->findBySlug('editor');
        self::assertNotNull($editor);
        $this->roles->grant(7, $editor->id, null, time(), time() - 3600);

        $row = $this->rowFor('editor', viewerLevel: 90);

        self::assertTrue($row->granted);
        self::assertTrue($row->expired);
        self::assertSame(date('Y-m-d', time() - 3600), $row->expiresAt);
    }

    public function testARoleAboveTheViewerIsListedButNotOffered(): void
    {
        self::assertFalse($this->rowFor('supervisor', viewerLevel: 70)->manageable);
        self::assertTrue($this->rowFor('editor', viewerLevel: 70)->manageable);
    }

    public function testAnAccountThatIsGoneIsReported(): void
    {
        $this->expectException(UserNotFoundException::class);

        $this->useCase->execute(404, viewerLevel: 90);
    }

    private function rowFor(string $slug, int $viewerLevel): UserRoleRowDTO
    {
        foreach ($this->useCase->execute(7, $viewerLevel)->rows as $row) {
            if ($row->slug === $slug) {
                return $row;
            }
        }

        self::fail('No row for the ' . $slug . ' role.');
    }

    /**
     * One account, id 7; anybody else is unknown.
     */
    private function users(): UserListRepositoryInterface
    {
        return new class implements UserListRepositoryInterface {
            public function findById(int $id): ?User
            {
                if ($id !== 7) {
                    return null;
                }

                $user = new User();
                $user->forceFill(['id' => 7, 'name' => 'Staff member']);

                return $user;
            }

            public function countApproved(): int
            {
                return 1;
            }

            public function getApproved(UserListSort $sort, int $limit, int $offset): EloquentCollection
            {
                return new EloquentCollection();
            }
        };
    }
}
