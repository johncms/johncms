<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Guestbook\UseCases;

use Johncms\Auth\Authentication\AuthenticatorChain;
use Johncms\Auth\Authorization\PermissionResolver;
use Johncms\Auth\Authorization\RoleLevels;
use Johncms\Auth\CurrentUser;
use Johncms\Auth\Identity;
use Johncms\Http\Request;
use Johncms\Modules\Guestbook\Application\Exceptions\GuestbookAccessDeniedException;
use Johncms\Modules\Guestbook\Application\Services\GuestbookPermissions;
use Johncms\Modules\Guestbook\Application\UseCases\EnsureGuestbookEntryManageAccessUseCase;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Users\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Support\FakeAccessChecker;
use Tests\Support\FakeAuthenticator;
use Tests\Support\FakeRoleRepository;
use Tests\Support\IdentityFactory;
use Tests\Support\UserFactory;

final class EnsureGuestbookEntryManageAccessUseCaseTest extends TestCase
{
    private FakeRoleRepository $roles;

    protected function setUp(): void
    {
        $this->roles = new FakeRoleRepository();
        $this->roles->add('user', level: 10, isDefault: true);
        $this->roles->add('moderator', level: 30);
        $this->roles->add('administrator', level: 70);
    }

    public function testThrowsWithoutThePermission(): void
    {
        $useCase = $this->useCase(IdentityFactory::user(id: 1, roles: ['user', 'moderator']), granted: []);

        $this->expectException(GuestbookAccessDeniedException::class);
        $useCase->execute($this->makeEntry(null));
    }

    public function testThrowsWhenTheAuthorStandsAbove(): void
    {
        $this->roles->grantTo(1, ['moderator']);
        $this->roles->grantTo(2, ['administrator']);

        $useCase = $this->useCase(IdentityFactory::user(id: 1, roles: ['user', 'moderator']), [GuestbookPermissions::ENTRY_MANAGE]);

        $this->expectException(GuestbookAccessDeniedException::class);
        $useCase->execute($this->makeEntry(UserFactory::make(attributes: ['id' => 2])));
    }

    public function testAllowsAnEntryOfAGuest(): void
    {
        $this->expectNotToPerformAssertions();

        $useCase = $this->useCase(IdentityFactory::user(id: 1, roles: ['user', 'moderator']), [GuestbookPermissions::ENTRY_MANAGE]);
        $useCase->execute($this->makeEntry(null));
    }

    public function testAllowsAnAuthorStandingLevelOrBelow(): void
    {
        $this->expectNotToPerformAssertions();

        $this->roles->grantTo(1, ['moderator']);
        $this->roles->grantTo(2, ['moderator']);

        $useCase = $this->useCase(IdentityFactory::user(id: 1, roles: ['user', 'moderator']), [GuestbookPermissions::ENTRY_MANAGE]);

        $useCase->execute($this->makeEntry(UserFactory::make(attributes: ['id' => 2])));
        $useCase->execute($this->makeEntry(UserFactory::make(attributes: ['id' => 3])));
    }

    /**
     * @param list<string> $granted
     */
    private function useCase(Identity $identity, array $granted): EnsureGuestbookEntryManageAccessUseCase
    {
        $stack = new RequestStack();
        $stack->push(Request::create('/guestbook', 'GET'));

        $currentUser = new CurrentUser(
            new AuthenticatorChain([new FakeAuthenticator($identity)]),
            new PermissionResolver($this->roles),
            $stack
        );

        return new EnsureGuestbookEntryManageAccessUseCase(
            new FakeAccessChecker($granted),
            $currentUser,
            new RoleLevels($this->roles)
        );
    }

    private function makeEntry(?User $author): GuestbookEntry
    {
        $entry = new GuestbookEntry();
        $entry->setRelation('user', $author);

        return $entry;
    }
}
