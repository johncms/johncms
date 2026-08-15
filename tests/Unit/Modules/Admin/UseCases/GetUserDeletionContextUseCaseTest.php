<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\UseCases;

use Johncms\Auth\Authentication\AuthenticatorChain;
use Johncms\Auth\Authorization\PermissionResolver;
use Johncms\Auth\Authorization\RoleLevels;
use Johncms\Auth\CurrentUser;
use Johncms\Auth\Identity;
use Johncms\Http\Request;
use Johncms\Modules\Admin\Application\Exceptions\CannotDeleteHigherRightsException;
use Johncms\Modules\Admin\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Admin\Application\Exceptions\WrongUserDataException;
use Johncms\Modules\Admin\Application\UseCases\GetUserDeletionContextUseCase;
use Johncms\Modules\Admin\Domain\Repository\UserDeletionRepositoryInterface;
use Johncms\Users\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\FakeAuthenticator;
use Tests\Support\FakeRoleRepository;
use Tests\Support\FakeUserRepository;
use Tests\Support\IdentityFactory;

final class GetUserDeletionContextUseCaseTest extends TestCase
{
    use BootsInMemoryDatabase;

    private FakeRoleRepository $roles;

    protected function setUp(): void
    {
        $this->bootDatabase();

        $this->roles = new FakeRoleRepository();
        $this->roles->add('user', level: 10, isDefault: true, isSystem: true);
        $this->roles->add('admin', level: 70, isSystem: true);
        $this->roles->add('supervisor', level: 90, isSystem: true);
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testAnOrdinaryAccountCanBeDeleted(): void
    {
        $this->roles->grantTo(1, ['admin']);

        $context = $this->useCaseFor(viewerId: 1)->execute(2);

        self::assertSame(2, $context->user->id);
    }

    /**
     * The rule that used to compare two numbers: nobody deletes an account standing above their
     * own, and it is the roles that say who stands where.
     */
    public function testAnAccountThatOutranksTheViewerIsRefused(): void
    {
        $this->roles->grantTo(1, ['admin']);
        $this->roles->grantTo(2, ['supervisor']);

        $this->expectException(CannotDeleteHigherRightsException::class);

        $this->useCaseFor(viewerId: 1)->execute(2);
    }

    public function testAnAccountOfTheSameStandingCanBeDeleted(): void
    {
        $this->roles->grantTo(1, ['admin']);
        $this->roles->grantTo(2, ['admin']);

        self::assertSame(2, $this->useCaseFor(viewerId: 1)->execute(2)->user->id);
    }

    public function testDeletingYourOwnAccountIsNotOfferedHere(): void
    {
        $this->expectException(WrongUserDataException::class);

        $this->useCaseFor(viewerId: 1)->execute(1);
    }

    public function testAnAccountThatIsGoneIsReported(): void
    {
        $this->expectException(UserNotFoundException::class);

        $this->useCaseFor(viewerId: 1)->execute(404);
    }

    private function useCaseFor(int $viewerId): GetUserDeletionContextUseCase
    {
        $stack = new RequestStack();
        $stack->push(Request::create('/admin/users/2/delete', 'GET'));

        $currentUser = new CurrentUser(
            new AuthenticatorChain([new FakeAuthenticator(IdentityFactory::user(id: $viewerId))]),
            new PermissionResolver($this->roles),
            $stack,
            new FakeUserRepository()
        );

        return new GetUserDeletionContextUseCase(
            $this->repository(),
            $currentUser,
            new RoleLevels($this->roles)
        );
    }

    /**
     * One account, id 2; anybody else is unknown.
     */
    private function repository(): UserDeletionRepositoryInterface
    {
        return new class implements UserDeletionRepositoryInterface {
            public function findById(int $id): ?User
            {
                if ($id !== 2) {
                    return null;
                }

                $user = new User();
                $user->forceFill(['id' => 2, 'name' => 'Target']);

                return $user;
            }

            public function countComments(int $id): int
            {
                return 0;
            }

            public function countForumTopics(int $id): int
            {
                return 0;
            }

            public function countForumPosts(int $id): int
            {
                return 0;
            }
        };
    }
}
