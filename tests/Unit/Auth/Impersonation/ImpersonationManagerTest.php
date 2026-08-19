<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Impersonation;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Johncms\Auth\Authentication\AuthenticatorChain;
use Johncms\Auth\AuthMethod;
use Johncms\Auth\CurrentUser;
use Johncms\Auth\Identity;
use Johncms\Auth\Impersonation\ImpersonationManager;
use Johncms\Auth\Impersonation\ImpersonationNotAllowedException;
use Johncms\Auth\Impersonation\ImpersonationSettings;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentAuthSessionRepository;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentRoleRepository;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Authorization\DefaultPermissions;
use Johncms\Auth\Authorization\PermissionRegistry;
use Johncms\Auth\Authorization\PermissionResolver;
use Johncms\Auth\Authorization\RoleLevels;
use Johncms\Auth\Authorization\RoleSeeder;
use Johncms\Auth\Authorization\SystemRole;
use Johncms\Auth\AuthTables;
use Johncms\Auth\Session\AuthCookieFactory;
use Johncms\Auth\Session\AuthSession;
use Johncms\Auth\Session\AuthSessionManager;
use Johncms\Auth\Session\SessionRevocationReason;
use Johncms\Auth\Session\SessionSettings;
use Johncms\Http\CookieQueue;
use Johncms\Http\Environment;
use Johncms\Http\Request;
use Johncms\Security\ClientInfoDTO;
use Johncms\Users\User;
use Gettext\Translator;
use Gettext\TranslatorFunctions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\RunsMigrations;
use Tests\Support\FakeAccessChecker;
use Tests\Support\FakeAuthenticator;
use Tests\Support\FakeUserRepository;
use Tests\Support\RecordingAuthEventLogger;

/**
 * Browsing as another account: what it opens, what it refuses and what it leaves behind.
 */
final class ImpersonationManagerTest extends TestCase
{
    use BootsInMemoryDatabase;
    use RunsMigrations;

    private const COOKIE = 'jc_auth';

    private const PARENT_COOKIE = 'jc_auth_parent';

    private AuthSessionManager $sessions;

    private EloquentRoleRepository $roles;

    private CookieQueue $cookieQueue;

    private RecordingAuthEventLogger $eventLogger;

    private ImpersonationSettings $settings;

    protected function setUp(): void
    {
        $this->bootDatabase();
        TranslatorFunctions::register(new Translator());
        $this->migrate('system', 'initial_auth_schema');
        $this->createUsersTable();

        $this->sessions = new AuthSessionManager(new EloquentAuthSessionRepository(), new SessionSettings());
        $this->roles = new EloquentRoleRepository();
        (new RoleSeeder($this->roles, new DefaultPermissions(new PermissionRegistry())))->seed();
        $this->cookieQueue = new CookieQueue();
        $this->eventLogger = new RecordingAuthEventLogger();
        $this->settings = new ImpersonationSettings();
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testStartingOpensASessionOfTheTargetCarryingTheAdministrator(): void
    {
        $admin = $this->createAdmin();
        $target = $this->createUser();
        $adminSession = $this->sessions->start($admin->id, true, $this->client());

        $this->managerFor($admin, $adminSession->session->id)
            ->start($target->id, $this->request([self::COOKIE => $adminSession->token]));

        $opened = AuthSession::query()->whereNotNull('impersonator_id')->firstOrFail();

        self::assertSame($target->id, $opened->user_id);
        self::assertSame($admin->id, $opened->impersonator_id);
        self::assertSame($adminSession->session->id, $opened->parent_session_id);
    }

    /**
     * The administrator's own session is not touched: it waits in a cookie of its own, which is
     * what makes closing the browser mid-impersonation harmless.
     */
    public function testTheAdministratorsOwnSessionIsParkedInTheParentCookie(): void
    {
        $admin = $this->createAdmin();
        $target = $this->createUser();
        $adminSession = $this->sessions->start($admin->id, true, $this->client());

        $this->managerFor($admin, $adminSession->session->id)
            ->start($target->id, $this->request([self::COOKIE => $adminSession->token]));

        self::assertSame($adminSession->token, $this->cookieValue(self::PARENT_COOKIE));
        self::assertNull($this->sessions->find($adminSession->token)?->revoked_at);
    }

    /**
     * An hour, never extended: an administrator who keeps clicking would otherwise stay somebody
     * else all day.
     */
    public function testTheOpenedSessionGetsTheImpersonationLifetime(): void
    {
        $admin = $this->createAdmin();
        $target = $this->createUser();
        $adminSession = $this->sessions->start($admin->id, true, $this->client());

        $this->managerFor($admin, $adminSession->session->id)
            ->start($target->id, $this->request([self::COOKIE => $adminSession->token]));

        $opened = AuthSession::query()->whereNotNull('impersonator_id')->firstOrFail();

        self::assertEqualsWithDelta(time() + $this->settings->lifetime, $opened->expires_at, 5);
        self::assertFalse($opened->remember);
    }

    public function testWithoutThePermissionNothingIsOpened(): void
    {
        $admin = $this->createAdmin();
        $target = $this->createUser();

        $this->expectException(ImpersonationNotAllowedException::class);

        $this->managerFor($admin, null, allowed: false)->start($target->id, $this->request());
    }

    /**
     * Otherwise browsing as somebody would be the way around every "you cannot act on somebody
     * who outranks you" check there is.
     */
    public function testAnAccountThatOutranksTheVisitorIsRefused(): void
    {
        $admin = $this->createUser();
        $this->grant($admin, SystemRole::Admin);
        $target = $this->createUser();
        $this->grant($target, SystemRole::Supervisor);

        $this->expectException(ImpersonationNotAllowedException::class);

        $this->managerFor($admin, null)->start($target->id, $this->request());
    }

    public function testBrowsingAsOneselfIsRefused(): void
    {
        $admin = $this->createAdmin();

        $this->expectException(ImpersonationNotAllowedException::class);

        $this->managerFor($admin, null)->start($admin->id, $this->request());
    }

    public function testStoppingRevokesTheImpersonatedSessionAndRestoresTheAdministrator(): void
    {
        $admin = $this->createAdmin();
        $target = $this->createUser();
        $adminSession = $this->sessions->start($admin->id, true, $this->client());
        $impersonated = $this->sessions->start(
            $target->id,
            false,
            $this->client(),
            impersonatorId: $admin->id,
            parentSessionId: $adminSession->session->id
        );

        $stopped = $this->managerFor($admin, $impersonated->session->id, impersonatorId: $admin->id, userId: $target->id)
            ->stop($this->request([
                self::COOKIE        => $impersonated->token,
                self::PARENT_COOKIE => $adminSession->token,
            ]));

        self::assertTrue($stopped);
        self::assertSame(
            SessionRevocationReason::ImpersonationStop->value,
            AuthSession::query()->findOrFail($impersonated->session->id)->revoked_reason
        );
        self::assertSame($adminSession->token, $this->cookieValue(self::COOKIE));
        self::assertSame('', $this->cookieValue(self::PARENT_COOKIE));
    }

    public function testStoppingOutsideAnImpersonationDoesNothing(): void
    {
        $admin = $this->createAdmin();

        self::assertFalse($this->managerFor($admin, null)->stop($this->request()));
        self::assertSame([], $this->cookieQueue->all());
    }

    public function testBothEndsAreRecorded(): void
    {
        $admin = $this->createAdmin();
        $target = $this->createUser();
        $adminSession = $this->sessions->start($admin->id, true, $this->client());

        $manager = $this->managerFor($admin, $adminSession->session->id);
        $manager->start($target->id, $this->request([self::COOKIE => $adminSession->token]));

        self::assertSame(['impersonation.start'], $this->eventLogger->events());
        self::assertSame($admin->id, $this->eventLogger->entries()[0]['actor_id']);
        self::assertSame($target->id, $this->eventLogger->entries()[0]['user_id']);
    }

    private function managerFor(
        User $admin,
        ?int $sessionId,
        bool $allowed = true,
        ?int $impersonatorId = null,
        ?int $userId = null,
    ): ImpersonationManager {
        $identity = new Identity(
            userId: $userId ?? $admin->id,
            method: AuthMethod::Session,
            impersonatorId: $impersonatorId,
            sessionId: $sessionId,
        );

        return new ImpersonationManager(
            $this->sessions,
            new EloquentAuthSessionRepository(),
            new AuthCookieFactory(new SessionSettings(), $this->settings),
            $this->cookieQueue,
            new Environment($this->requestStack()),
            $this->currentUser($identity),
            new FakeAccessChecker($allowed ? [CorePermissions::USERS_IMPERSONATE] : []),
            new RoleLevels($this->roles),
            new FakeUserRepository(User::query()->get()->all()),
            $this->eventLogger,
            $this->settings,
        );
    }

    /**
     * The real resolver over the real roles table: the level check is exactly what these tests are
     * about, and roles held in memory would answer it with zeros.
     */
    private function currentUser(Identity $identity): CurrentUser
    {
        return new CurrentUser(
            new AuthenticatorChain([new FakeAuthenticator($identity)]),
            new PermissionResolver($this->roles),
            $this->requestStack(),
            new FakeUserRepository(User::query()->get()->all())
        );
    }

    private function requestStack(): RequestStack
    {
        $stack = new RequestStack();
        $stack->push(Request::create('/'));

        return $stack;
    }

    /**
     * @param array<string, string> $cookies
     */
    private function request(array $cookies = []): Request
    {
        return Request::create('/', 'POST', [], $cookies);
    }

    private function cookieValue(string $name): ?string
    {
        foreach ($this->cookieQueue->all() as $cookie) {
            /** @var Cookie $cookie */
            if ($cookie->getName() === $name) {
                return $cookie->getValue();
            }
        }

        return null;
    }

    private function client(): ClientInfoDTO
    {
        return new ClientInfoDTO('192.0.2.10', '', 'Mozilla/5.0');
    }

    private function roleId(SystemRole $role): int
    {
        return (int) $this->roles->findBySlug($role->value)?->id;
    }

    /**
     * An account that may browse as the ordinary users of the site.
     */
    private function createAdmin(): User
    {
        $admin = $this->createUser();
        $this->grant($admin, SystemRole::Supervisor);

        return $admin;
    }

    private function grant(User $user, SystemRole $role): void
    {
        $this->roles->grant($user->id, $this->roleId($role), null, time());
    }

    private function createUser(): User
    {
        $user = new User();
        $user->fill(['name' => 'user-' . uniqid()]);
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
            }
        );
    }
}
