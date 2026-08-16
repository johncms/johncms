<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Events;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Auth\AuthMethod;
use Johncms\Auth\CurrentUser;
use Johncms\Auth\Events\AuthEvent;
use Johncms\Auth\Events\AuthEventLogger;
use Johncms\Auth\Events\AuthEventType;
use Johncms\Auth\Identity;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentAuthEventRepository;
use Johncms\Auth\Schema\AuthSchema;
use Johncms\Http\Environment;
use Johncms\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\CurrentUserFactory;
use Tests\Support\IdentityFactory;

final class AuthEventLoggerTest extends TestCase
{
    use BootsInMemoryDatabase;

    protected function setUp(): void
    {
        $this->bootDatabase();
        AuthSchema::create(Capsule::schema());
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testTheEntryCarriesTheAddressAndTheBrowserOfTheRequest(): void
    {
        $this->loggerFor(CurrentUserFactory::guest())->log(AuthEventType::LoginSuccess, 7, now: 1000);

        $entry = AuthEvent::query()->firstOrFail();

        self::assertSame('login.success', $entry->event);
        self::assertSame(7, $entry->user_id);
        self::assertSame('192.0.2.10', $entry->ip);
        self::assertSame('Mozilla/5.0', $entry->user_agent);
        self::assertSame(1000, $entry->created_at);
    }

    /**
     * The context is what tells two entries of the same kind apart, so it has to survive the trip
     * through the database intact.
     */
    public function testTheContextIsStoredAsGivenAndComesBackAsAnArray(): void
    {
        $this->loggerFor(CurrentUserFactory::guest())->log(
            AuthEventType::LoginFailed,
            context: ['login' => 'tester', 'reason' => 'invalid_credentials']
        );

        $entry = AuthEvent::query()->firstOrFail();

        self::assertSame(['login' => 'tester', 'reason' => 'invalid_credentials'], $entry->context);
    }

    public function testAVisitorActingOnTheirOwnAccountIsNotRepeatedAsTheActor(): void
    {
        $this->loggerFor(CurrentUserFactory::for(IdentityFactory::user(id: 7)))
            ->log(AuthEventType::PasswordChanged, 7);

        self::assertNull(AuthEvent::query()->firstOrFail()->actor_id);
    }

    /**
     * The whole point of the column: an administrator changing somebody else's password is named,
     * so the trail answers "who did this to my account".
     */
    public function testAnAdministratorActingOnSomebodyElseIsNamedAsTheActor(): void
    {
        $this->loggerFor(CurrentUserFactory::for(IdentityFactory::superAdmin(id: 1)))
            ->log(AuthEventType::PasswordChanged, 7);

        self::assertSame(1, AuthEvent::query()->firstOrFail()->actor_id);
    }

    /**
     * During impersonation the request belongs to the user, but the person behind it is the
     * administrator — and that is who the record has to name.
     */
    public function testTheImpersonatorIsTheActorRatherThanTheAccountBeingBrowsedAs(): void
    {
        $identity = new Identity(userId: 7, method: AuthMethod::Session, impersonatorId: 1);

        $this->loggerFor(CurrentUserFactory::for($identity))->log(AuthEventType::PasswordChanged, 7);

        self::assertSame(1, AuthEvent::query()->firstOrFail()->actor_id);
    }

    public function testAnEventFromAModuleIsStoredUnderItsOwnKey(): void
    {
        $this->loggerFor(CurrentUserFactory::guest())->log('shop.order.paid', 7);

        $entry = AuthEvent::query()->firstOrFail();

        self::assertSame('shop.order.paid', $entry->event);
        // Nothing in the core knows this key, and reading the row must not fail because of it.
        self::assertNull($entry->type());
    }

    private function loggerFor(CurrentUser $currentUser): AuthEventLogger
    {
        $stack = new RequestStack();
        $stack->push(
            Request::create('/', server: ['REMOTE_ADDR' => '192.0.2.10', 'HTTP_USER_AGENT' => 'Mozilla/5.0'])
        );

        return new AuthEventLogger(new EloquentAuthEventRepository(), new Environment($stack), $currentUser);
    }
}
