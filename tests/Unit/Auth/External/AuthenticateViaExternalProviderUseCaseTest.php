<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\External;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Johncms\Auth\External\AuthenticateViaExternalProviderUseCase;
use Johncms\Auth\External\ExternalAccountConflictException;
use Johncms\Auth\External\ExternalAuthStatus;
use Johncms\Auth\External\ExternalIdentityDTO;
use Johncms\Auth\External\UserIdentity;
use Johncms\Auth\Authorization\AccessChecker;
use Johncms\Auth\Authorization\DefaultPermissions;
use Johncms\Auth\Authorization\PermissionRegistry;
use Johncms\Auth\Authorization\PermissionResolver;
use Johncms\Auth\Authorization\RoleSeeder;
use Johncms\Auth\Authorization\Voters\RolePermissionVoter;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentRoleRepository;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentUserIdentityRepository;
use Johncms\Auth\AuthTables;
use Johncms\Users\User;
use Gettext\Translator;
use Gettext\TranslatorFunctions;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\RunsMigrations;
use Tests\Support\CurrentUserFactory;
use Tests\Support\RecordingAuthEventLogger;

/**
 * What an identity from an external service means for this site.
 *
 * The interesting half is what it must refuse: matching accounts by email address is how these
 * features turn into account takeovers.
 */
final class AuthenticateViaExternalProviderUseCaseTest extends TestCase
{
    use BootsInMemoryDatabase;
    use RunsMigrations;

    private const PROVIDER = 'github';

    private RecordingAuthEventLogger $eventLogger;

    protected function setUp(): void
    {
        $this->bootDatabase();
        // The built-in roles are named through the gettext helpers.
        TranslatorFunctions::register(new Translator());
        $this->migrate('system', 'initial_auth_schema');
        $this->createUsersTable();

        $this->eventLogger = new RecordingAuthEventLogger();
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testAKnownIdentitySignsItsOwnerIn(): void
    {
        $user = $this->createUser();
        $this->useCase()->link($user->id, self::PROVIDER, $this->identity('42'));

        $result = $this->useCase()->execute(self::PROVIDER, $this->identity('42'));

        self::assertSame(ExternalAuthStatus::SignedIn, $result->status);
        self::assertSame($user->id, $result->userId);
    }

    /**
     * The only case where matching by address is safe: the provider verified it and the account
     * here confirmed it too.
     */
    public function testAVerifiedAddressLinksToTheConfirmedAccountItBelongsTo(): void
    {
        $user = $this->createUser(email: 'owner@example.com', confirmed: true);

        $result = $this->useCase()->execute(
            self::PROVIDER,
            $this->identity('42', email: 'owner@example.com', verified: true)
        );

        self::assertSame(ExternalAuthStatus::SignedIn, $result->status);
        self::assertSame($user->id, $result->userId);
        self::assertSame(1, UserIdentity::query()->where('user_id', '=', $user->id)->count());
    }

    /**
     * The takeover this guards against: registering at a provider with somebody else's address
     * and walking into their account here.
     */
    public function testAnUnverifiedAddressIsRefusedRatherThanLinked(): void
    {
        $this->createUser(email: 'owner@example.com', confirmed: true);

        $this->expectException(ExternalAccountConflictException::class);

        $this->useCase()->execute(self::PROVIDER, $this->identity('42', email: 'owner@example.com'));
    }

    /**
     * The other half of the same rule: our own account never confirmed the address, so it proves
     * nothing about who owns it.
     */
    public function testAnUnconfirmedAccountIsRefusedEvenForAVerifiedAddress(): void
    {
        $this->createUser(email: 'owner@example.com', confirmed: false);

        $this->expectException(ExternalAccountConflictException::class);

        $this->useCase()->execute(
            self::PROVIDER,
            $this->identity('42', email: 'owner@example.com', verified: true)
        );
    }

    public function testANewcomerIsSentToFinishTheirProfile(): void
    {
        $result = $this->useCase()->execute(self::PROVIDER, $this->identity('42', email: 'new@example.com'));

        self::assertSame(ExternalAuthStatus::NeedsProfile, $result->status);
    }

    /**
     * A closed site lets existing accounts in through a provider and creates none.
     */
    public function testRegistrationBeingClosedStopsANewcomer(): void
    {
        $result = $this->useCase(registrationOpen: false)
            ->execute(self::PROVIDER, $this->identity('42', email: 'new@example.com'));

        self::assertSame(ExternalAuthStatus::RegistrationClosed, $result->status);
    }

    /**
     * These come from somebody else's API: VK sends avatar URLs several hundred characters long,
     * and one of them failed a whole sign-in with a database error before the column was widened.
     */
    public function testOversizedProfileValuesDoNotBreakTheLink(): void
    {
        $user = $this->createUser();
        $identity = new ExternalIdentityDTO(
            providerUserId: '42',
            email: str_repeat('a', 200) . '@example.com',
            emailVerified: false,
            nickname: str_repeat('n', 300),
            avatarUrl: 'https://example.com/' . str_repeat('x', 400) . '.jpg',
        );

        $this->useCase()->link($user->id, self::PROVIDER, $identity);

        $link = UserIdentity::query()->firstOrFail();

        self::assertSame(191, mb_strlen((string) $link->email));
        self::assertSame(191, mb_strlen((string) $link->nickname));
        // The address of a picture is worthless once it is cut, so this column holds text.
        self::assertSame(424, mb_strlen((string) $link->avatar_url));
    }

    public function testSigningInThroughAProviderIsRecorded(): void
    {
        $user = $this->createUser();
        $useCase = $this->useCase();
        $useCase->link($user->id, self::PROVIDER, $this->identity('42'));
        $useCase->execute(self::PROVIDER, $this->identity('42'));

        self::assertSame(['oauth.linked', 'oauth.login'], $this->eventLogger->events());
    }

    /**
     * The real checker and the real resolver over the real roles table. A stubbed checker would
     * hide the mistake these tests exist to catch: the permission is asked of the guest role, and
     * a guest identity carries no roles until the resolver fills them in — so an unresolved one
     * is refused everything, which reads as "registration is closed" on a site where it is open.
     */
    private function useCase(bool $registrationOpen = true): AuthenticateViaExternalProviderUseCase
    {
        $roles = new EloquentRoleRepository();
        (new RoleSeeder($roles, new DefaultPermissions(new PermissionRegistry())))->seed();

        $guest = $roles->guestRole();

        if ($guest !== null) {
            $roles->setPermissions($guest->id, $registrationOpen ? ['registration.register'] : []);
        }

        return new AuthenticateViaExternalProviderUseCase(
            new EloquentUserIdentityRepository(),
            new AccessChecker(CurrentUserFactory::guest(), [new RolePermissionVoter()]),
            new PermissionResolver($roles),
            $this->eventLogger
        );
    }

    private function identity(string $id, ?string $email = null, bool $verified = false): ExternalIdentityDTO
    {
        return new ExternalIdentityDTO($id, $email, $verified, 'nickname');
    }

    private function createUser(?string $email = null, bool $confirmed = false): User
    {
        $user = new User();
        $user->fill(
            [
                'name'            => 'user-' . uniqid(),
                'mail'            => $email ?? '',
                'email_confirmed' => $confirmed ? 1 : null,
            ]
        );
        $user->save();

        return $user;
    }

    private function createUsersTable(): void
    {
        Capsule::schema()->create(
            'users',
            static function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name')->default('');
                $table->string('mail')->default('');
                $table->string('password')->default('');
                $table->boolean('email_confirmed')->nullable();
            }
        );
    }
}
