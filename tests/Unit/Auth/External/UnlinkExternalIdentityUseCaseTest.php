<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\External;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Johncms\Auth\External\ExternalAuthException;
use Johncms\Auth\External\UnlinkExternalIdentityUseCase;
use Johncms\Auth\External\UserIdentity;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentUserIdentityRepository;
use Johncms\Auth\Schema\AuthSchema;
use Johncms\Users\User;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\FakeUserRepository;
use Tests\Support\RecordingAuthEventLogger;

/**
 * Detaching a service, and the rule that an account may never be left without a way in.
 */
final class UnlinkExternalIdentityUseCaseTest extends TestCase
{
    use BootsInMemoryDatabase;

    private UnlinkExternalIdentityUseCase $useCase;

    private RecordingAuthEventLogger $eventLogger;

    private User $user;

    protected function setUp(): void
    {
        $this->bootDatabase();
        TranslatorFunctions::register(new Translator());
        AuthSchema::create(Capsule::schema());
        $this->createUsersTable();

        $this->user = new User();
        $this->user->fill(['name' => 'tester']);
        $this->user->save();

        $this->eventLogger = new RecordingAuthEventLogger();
        $this->useCase = new UnlinkExternalIdentityUseCase(
            new EloquentUserIdentityRepository(),
            new FakeUserRepository([$this->user]),
            $this->eventLogger
        );
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testAServiceIsDetachedWhenAPasswordRemains(): void
    {
        $this->user->password = 'hashed';
        $this->link('github');

        $this->useCase->execute($this->user->id, 'github');

        self::assertSame(0, UserIdentity::query()->count());
        self::assertSame(['oauth.unlinked'], $this->eventLogger->events());
    }

    public function testASecondServiceIsEnoughOfAnAlternative(): void
    {
        $this->link('github');
        $this->link('google');

        $this->useCase->execute($this->user->id, 'github');

        self::assertSame(['google'], UserIdentity::query()->pluck('provider')->all());
    }

    /**
     * Somebody who signed up through a service has no password, and password recovery cannot help
     * them: there is nothing to reset. Detaching their only service would lock them out for good.
     */
    public function testTheOnlyWayInCannotBeDetached(): void
    {
        $this->link('github');

        $this->expectException(ExternalAuthException::class);

        $this->useCase->execute($this->user->id, 'github');
    }

    public function testDetachingSomethingThatIsNotLinkedDoesNothing(): void
    {
        $this->useCase->execute($this->user->id, 'github');

        self::assertSame([], $this->eventLogger->events());
    }

    private function link(string $provider): void
    {
        UserIdentity::query()->create(
            [
                'user_id'          => $this->user->id,
                'provider'         => $provider,
                'provider_user_id' => $provider . '-42',
                'linked_at'        => time(),
            ]
        );
    }

    private function createUsersTable(): void
    {
        Capsule::schema()->create(
            'users',
            static function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name')->default('');
                $table->string('password')->default('');
            }
        );
    }
}
