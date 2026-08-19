<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\UseCases;

use Johncms\Auth\Authorization\PermissionRegistry;
use Johncms\Auth\Authorization\DefaultPermissions;
use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Johncms\Auth\Authorization\RoleSeeder;
use Johncms\Auth\Authorization\SystemRole;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentRoleRepository;
use Johncms\Auth\AuthTables;
use Johncms\Modules\Admin\Application\UseCases\UpdateUserRolesUseCase;
use Johncms\Users\User;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\RunsMigrations;
use Tests\Support\RecordingAuthEventLogger;

final class UpdateUserRolesUseCaseTest extends TestCase
{
    use BootsInMemoryDatabase;
    use RunsMigrations;

    private EloquentRoleRepository $roles;

    private UpdateUserRolesUseCase $useCase;

    private RecordingAuthEventLogger $eventLogger;

    protected function setUp(): void
    {
        $this->bootDatabase();
        // The built-in roles are named through the gettext helpers, which nothing has registered
        // in an isolated unit test.
        TranslatorFunctions::register(new Translator());
        $this->migrate('system', 'initial_auth_schema');
        $this->createUsersTable();

        $this->roles = new EloquentRoleRepository();
        (new RoleSeeder($this->roles, new DefaultPermissions(new PermissionRegistry())))->seed();

        $this->eventLogger = new RecordingAuthEventLogger();
        $this->useCase = new UpdateUserRolesUseCase($this->roles, $this->eventLogger);
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testTickingARoleGrantsIt(): void
    {
        $user = $this->createUser();

        $this->useCase->execute($user->id, [$this->roleId(SystemRole::Admin) => null], viewerLevel: 90);

        self::assertSame([SystemRole::Admin->value], $this->slugsOf($user->id));
    }

    public function testARoleThatIsNoLongerTickedIsTakenAway(): void
    {
        $user = $this->createUser();
        $this->useCase->execute($user->id, [$this->roleId(SystemRole::Admin) => null], viewerLevel: 90);

        $this->useCase->execute($user->id, [], viewerLevel: 90);

        self::assertSame([], $this->slugsOf($user->id));
    }

    /**
     * Temporary moderation: the row stops counting on its own, without anybody clearing it.
     */
    public function testAGrantCanBeGivenAnEndDate(): void
    {
        $user = $this->createUser();
        $expiresAt = time() + 86400;

        $this->useCase->execute($user->id, [$this->roleId(SystemRole::ForumModerator) => $expiresAt], viewerLevel: 90);

        self::assertSame([$this->roleId(SystemRole::ForumModerator) => $expiresAt], $this->roles->grantsFor($user->id));
    }

    /**
     * The form never shows a role standing above the visitor, so a request that omits it must not
     * be read as "take it away" — that would be a way to demote somebody who outranks you.
     */
    public function testARoleAboveTheViewerIsLeftAloneByASaveThatOmitsIt(): void
    {
        $user = $this->createUser();
        $this->roles->grant($user->id, $this->roleId(SystemRole::Supervisor), null, time());

        $this->useCase->execute($user->id, [$this->roleId(SystemRole::ForumModerator) => null], viewerLevel: 70);

        self::assertSame(
            [SystemRole::ForumModerator->value, SystemRole::Supervisor->value],
            $this->slugsOf($user->id)
        );
    }

    /**
     * Recorded by slug rather than by id: the log is read long after the fact, and a role deleted
     * since must still be readable in it.
     */
    public function testGrantingAndTakingARoleAwayAreBothRecorded(): void
    {
        $user = $this->createUser();
        $moderator = $this->roleId(SystemRole::ForumModerator);

        $this->useCase->execute($user->id, [$moderator => null], viewerLevel: 90);
        $this->useCase->execute($user->id, [], viewerLevel: 90);

        self::assertSame(['role.granted', 'role.revoked'], $this->eventLogger->events());
        self::assertSame(
            SystemRole::ForumModerator->value,
            $this->eventLogger->entries()[0]['context']['role']
        );
    }

    public function testASaveThatChangesNothingRecordsNothing(): void
    {
        $user = $this->createUser();
        $moderator = $this->roleId(SystemRole::ForumModerator);

        $this->useCase->execute($user->id, [$moderator => null], viewerLevel: 90);
        $this->useCase->execute($user->id, [$moderator => null], viewerLevel: 90);

        self::assertSame(['role.granted'], $this->eventLogger->events());
    }

    /**
     * @return list<string>
     */
    private function slugsOf(int $userId): array
    {
        return $this->roles->grantedTo($userId, time())->pluck('slug')->sort()->values()->all();
    }


    private function roleId(SystemRole $role): int
    {
        return (int) $this->roles->findBySlug($role->value)?->id;
    }

    private function createUser(): User
    {
        $user = new User();
        $user->fill(['name' => 'staff-' . uniqid()]);
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
