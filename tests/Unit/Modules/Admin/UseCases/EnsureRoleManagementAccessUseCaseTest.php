<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\UseCases;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Auth\Authentication\AuthenticatorChain;
use Johncms\Auth\Authorization\AccessChecker;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Authorization\PermissionResolver;
use Johncms\Auth\Authorization\RoleLevels;
use Johncms\Auth\Authorization\Voters\RolePermissionVoter;
use Johncms\Auth\Authorization\Voters\SuperAdminVoter;
use Johncms\Auth\CurrentUser;
use Johncms\Auth\Identity;
use Johncms\Http\Request;
use Johncms\Modules\Admin\Application\Exceptions\RoleAccessDeniedException;
use Johncms\Modules\Admin\Application\UseCases\EnsureRoleManagementAccessUseCase;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\FakeAuthenticator;
use Tests\Support\FakeRoleRepository;
use Tests\Support\IdentityFactory;

final class EnsureRoleManagementAccessUseCaseTest extends TestCase
{
    use BootsInMemoryDatabase;

    private FakeRoleRepository $roles;

    protected function setUp(): void
    {
        $this->bootDatabase();
        // The guard names its refusals through the gettext helpers, which nothing has registered
        // in an isolated unit test.
        TranslatorFunctions::register(new Translator());

        $this->roles = new FakeRoleRepository();
        $this->roles->add('admin', [CorePermissions::ADMIN_ROLES_MANAGE], level: 70, isSystem: true);
        $this->roles->add('supervisor', level: 90, isSystem: true);
        $this->roles->add('editor', level: 20);
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testWithoutThePermissionNothingIsOpened(): void
    {
        $useCase = $this->useCaseFor(IdentityFactory::user(id: 5));

        $this->expectException(RoleAccessDeniedException::class);

        $useCase->execute();
    }

    public function testARoleAtOrBelowTheOwnLevelIsOpened(): void
    {
        $this->roles->grantTo(1, ['admin']);
        $useCase = $this->useCaseFor(IdentityFactory::user(id: 1));

        $useCase->execute($this->roles->findBySlug('editor'));
        $useCase->execute($this->roles->findBySlug('admin'));

        self::assertSame(70, $useCase->maxLevel());
    }

    /**
     * The point of the level: the editor may hand out what the visitor holds, never what stands
     * above them. Without this an administrator would simply grant themselves supervisor.
     */
    public function testARoleAboveTheOwnLevelIsOutOfReach(): void
    {
        $this->roles->grantTo(1, ['admin']);
        $useCase = $this->useCaseFor(IdentityFactory::user(id: 1));

        $this->expectException(RoleAccessDeniedException::class);

        $useCase->execute($this->roles->findBySlug('supervisor'));
    }

    public function testARoleCannotBePlacedAboveTheOwnLevel(): void
    {
        $this->roles->grantTo(1, ['admin']);
        $useCase = $this->useCaseFor(IdentityFactory::user(id: 1));

        $this->expectException(RoleAccessDeniedException::class);

        $useCase->execute(null, 90);
    }

    private function useCaseFor(Identity $identity): EnsureRoleManagementAccessUseCase
    {
        $stack = new RequestStack();
        $stack->push(Request::create('/admin/roles', 'GET'));

        $currentUser = new CurrentUser(
            new AuthenticatorChain([new FakeAuthenticator($identity)]),
            new PermissionResolver($this->roles),
            $stack
        );

        $levels = new RoleLevels($this->roles);

        return new EnsureRoleManagementAccessUseCase(
            new AccessChecker($currentUser, [new RolePermissionVoter(), new SuperAdminVoter($levels)]),
            $currentUser,
            $levels
        );
    }
}
