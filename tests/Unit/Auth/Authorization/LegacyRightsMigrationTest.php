<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Authorization;

use Johncms\Auth\Authorization\PermissionRegistry;
use Johncms\Auth\Authorization\DefaultPermissions;
use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Johncms\Auth\Authorization\LegacyRightsMigration;
use Johncms\Auth\Authorization\RoleSeeder;
use Johncms\Auth\Authorization\SystemRole;
use Johncms\Auth\Authorization\UserRole;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentRoleRepository;
use Johncms\Auth\AuthTables;
use Johncms\Users\User;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\RunsMigrations;

final class LegacyRightsMigrationTest extends TestCase
{
    use BootsInMemoryDatabase;
    use RunsMigrations;

    private EloquentRoleRepository $roles;

    private LegacyRightsMigration $migration;

    protected function setUp(): void
    {
        $this->bootDatabase();
        // The seeder names the roles through the gettext helpers, which nothing has registered
        // in an isolated unit test.
        TranslatorFunctions::register(new Translator());
        $this->migrate('system', 'initial_auth_schema');
        $this->createUsersTable();

        $this->roles = new EloquentRoleRepository();
        (new RoleSeeder($this->roles, new DefaultPermissions(new PermissionRegistry())))->seed();

        $this->migration = new LegacyRightsMigration($this->roles);
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testEachAccessLevelBecomesItsRole(): void
    {
        $admin = $this->createUser(rights: 7);
        $forumModerator = $this->createUser(rights: 3);

        $this->migration->migrate();

        self::assertSame([SystemRole::Admin->value], $this->slugsOf($admin));
        self::assertSame([SystemRole::ForumModerator->value], $this->slugsOf($forumModerator));
    }


    /**
     * @return list<string>
     */
    private function slugsOf(User $user): array
    {
        return $this->roles->grantedTo($user->id, time())->pluck('slug')->values()->all();
    }

    private function createUser(int $rights): User
    {
        // forceFill: the column is not part of the model any more, and the migration is the last
        // thing that reads it — with the query builder, on a site that has not dropped it yet.
        $user = new User();
        $user->forceFill(['name' => 'u' . $rights . '-' . uniqid(), 'rights' => $rights]);
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
                $table->integer('rights')->default(0);
            }
        );
    }
}
