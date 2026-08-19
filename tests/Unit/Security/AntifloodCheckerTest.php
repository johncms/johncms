<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Identity;
use Johncms\Auth\AuthTables;
use Johncms\Config\ConfigRepository;
use Johncms\Security\AntifloodChecker;
use Johncms\Users\User;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\RunsMigrations;
use Tests\Support\CurrentUserFactory;

final class AntifloodCheckerTest extends TestCase
{
    use BootsInMemoryDatabase;
    use RunsMigrations;

    protected function setUp(): void
    {
        $this->bootDatabase();
        $this->migrate('system', 'initial_auth_schema');
        $this->createUsersTable();
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testStaffWaitTheShortFixedDelay(): void
    {
        $this->configureAntiflood(day: 60, night: 60, mode: 3);

        $checker = new AntifloodChecker(CurrentUserFactory::withProfile($this->userWhoJustPosted()), $this->accessChecker(allows: true));

        self::assertSame(4, $checker->getRemainingSeconds());
    }

    public function testEverybodyElseWaitsWhatTheSiteConfigured(): void
    {
        $this->configureAntiflood(day: 60, night: 60, mode: 3);

        $checker = new AntifloodChecker(CurrentUserFactory::withProfile($this->userWhoJustPosted()), $this->accessChecker(allows: false));

        self::assertSame(60, $checker->getRemainingSeconds());
    }

    /**
     * The adaptive mode asks whether anybody of the staff is around. Staff is now "holds a role
     * that was granted", not "has a number above zero".
     */
    public function testTheAdaptiveModeSeesTheStaffThroughTheirRoles(): void
    {
        $this->configureAntiflood(day: 10, night: 300, mode: 1);
        $checker = new AntifloodChecker(CurrentUserFactory::withProfile($this->userWhoJustPosted()), $this->accessChecker(allows: false));

        self::assertSame(300, $checker->getRemainingSeconds(), 'Nobody of the staff is online');

        $moderator = $this->createUser(lastdate: time());
        Capsule::table(AuthTables::USER_ROLES)->insert(
            ['user_id' => $moderator->id, 'role_id' => 3, 'granted_at' => time()]
        );

        self::assertSame(10, $checker->getRemainingSeconds());
    }

    public function testAGrantThatRanOutDoesNotCountAsStaffOnline(): void
    {
        $this->configureAntiflood(day: 10, night: 300, mode: 1);
        $moderator = $this->createUser(lastdate: time());
        Capsule::table(AuthTables::USER_ROLES)->insert(
            [
                'user_id'    => $moderator->id,
                'role_id'    => 3,
                'granted_at' => time() - 100,
                'expires_at' => time() - 10,
            ]
        );

        $checker = new AntifloodChecker(CurrentUserFactory::withProfile($this->userWhoJustPosted()), $this->accessChecker(allows: false));

        self::assertSame(300, $checker->getRemainingSeconds());
    }

    private function configureAntiflood(int $day, int $night, int $mode): void
    {
        ConfigRepository::init(['johncms' => ['antiflood' => ['day' => $day, 'night' => $night, 'mode' => $mode]]]);
    }

    private function userWhoJustPosted(): User
    {
        $user = new User();
        $user->forceFill(['id' => 1, 'lastpost' => time()]);

        return $user;
    }

    private function createUser(int $lastdate): User
    {
        $user = new User();
        $user->fill(['name' => 'staff-' . uniqid(), 'lastdate' => $lastdate]);
        $user->save();

        return $user;
    }

    private function accessChecker(bool $allows): AccessCheckerInterface
    {
        return new class ($allows) implements AccessCheckerInterface {
            public function __construct(private readonly bool $allows)
            {
            }

            public function allows(string $permission, mixed $subject = null): bool
            {
                return $permission === CorePermissions::ANTIFLOOD_RELAXED && $this->allows;
            }

            public function allowsFor(Identity $identity, string $permission, mixed $subject = null): bool
            {
                return $this->allows($permission, $subject);
            }
        };
    }

    private function createUsersTable(): void
    {
        Capsule::schema()->create(
            'users',
            static function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name')->default('');
                $table->integer('lastdate')->default(0);
                $table->integer('lastpost')->default(0);
            }
        );
    }
}
