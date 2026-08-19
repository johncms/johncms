<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\UseCases;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Johncms\Auth\Events\AuthEventType;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentAuthEventRepository;
use Johncms\Auth\AuthTables;
use Johncms\Modules\Admin\Application\UseCases\GetAuthLogUseCase;
use Johncms\Modules\Admin\Infrastructure\Persistence\Repository\EloquentAuthLogRepository;
use Johncms\Users\User;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\RunsMigrations;

final class GetAuthLogUseCaseTest extends TestCase
{
    use BootsInMemoryDatabase;
    use RunsMigrations;

    private EloquentAuthEventRepository $events;

    private GetAuthLogUseCase $useCase;

    protected function setUp(): void
    {
        $this->bootDatabase();
        // The event labels are named through the gettext helpers, which nothing has registered in
        // an isolated unit test.
        TranslatorFunctions::register(new Translator());
        $this->migrate('system', 'initial_auth_schema');
        $this->createUsersTable();

        $this->events = new EloquentAuthEventRepository();
        $this->useCase = new GetAuthLogUseCase(new EloquentAuthLogRepository());
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testTheNewestEntryComesFirst(): void
    {
        $this->store(AuthEventType::LoginSuccess->value, 7, createdAt: 100);
        $this->store(AuthEventType::Logout->value, 7, createdAt: 200);

        $rows = $this->useCase->getPage(10, 0);

        self::assertSame(['logout', 'login.success'], array_column($rows, 'event'));
    }

    public function testTheNamesBehindTheIdsAreFilledIn(): void
    {
        $user = $this->createUser('Tester');
        $admin = $this->createUser('Root');
        $this->store(AuthEventType::RoleGranted->value, $user->id, $admin->id);

        $row = $this->useCase->getPage(10, 0)[0];

        self::assertSame('Tester', $row->userName);
        self::assertSame('Root', $row->actorName);
    }

    /**
     * "Everything about this user" has to include what they did to somebody else, or the filter
     * would hide exactly the entries an investigation is after.
     */
    public function testFilteringByUserFindsThemAsTheActorToo(): void
    {
        $this->store(AuthEventType::LoginSuccess->value, 7);
        $this->store(AuthEventType::RoleGranted->value, 9, 7);
        $this->store(AuthEventType::LoginSuccess->value, 8);

        self::assertSame(2, $this->useCase->count(userId: 7));
        self::assertCount(2, $this->useCase->getPage(10, 0, userId: 7));
    }

    public function testFilteringByEventNarrowsTheList(): void
    {
        $this->store(AuthEventType::LoginSuccess->value, 7);
        $this->store(AuthEventType::LoginFailed->value, 7);

        self::assertSame(1, $this->useCase->count(event: AuthEventType::LoginFailed->value));
    }

    /**
     * A module may log events of its own, and a module that has since been removed leaves rows
     * with a key nothing knows. The screen must show them rather than fail on them.
     */
    public function testAnUnknownEventIsShownUnderItsOwnKey(): void
    {
        $this->store('shop.order.paid', 7);

        $row = $this->useCase->getPage(10, 0)[0];

        self::assertSame('shop.order.paid', $row->label);
    }

    public function testTheContextIsFlattenedIntoLines(): void
    {
        $this->store(AuthEventType::LoginFailed->value, null, context: ['reason' => 'invalid_credentials']);

        self::assertSame(['reason: invalid_credentials'], $this->useCase->getPage(10, 0)[0]->details);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function store(
        string $event,
        ?int $userId = null,
        ?int $actorId = null,
        array $context = [],
        int $createdAt = 1000,
    ): void {
        $this->events->store(
            [
                'user_id'    => $userId,
                'actor_id'   => $actorId,
                'event'      => $event,
                'ip'         => '192.0.2.10',
                'user_agent' => 'Mozilla/5.0',
                'context'    => $context === [] ? null : $context,
                'created_at' => $createdAt,
            ]
        );
    }

    private function createUser(string $name): User
    {
        $user = new User();
        $user->fill(['name' => $name]);
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
