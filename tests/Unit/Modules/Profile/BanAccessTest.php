<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Profile;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Authorization\RoleLevels;
use Johncms\Auth\Identity;
use Johncms\Modules\Profile\Application\Access\BanAccess;
use Johncms\Users\User;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\CurrentUserFactory;
use Tests\Support\FakeRoleRepository;
use Tests\Support\IdentityFactory;
use Tests\Support\RunsMigrations;

final class BanAccessTest extends TestCase
{
    use BootsInMemoryDatabase;
    use RunsMigrations;

    /** The visitor whose bans the access reads. */
    private const int VISITOR = 7;

    protected function setUp(): void
    {
        $this->bootDatabase();
        $this->migrate('system', 'initial_core_schema');
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testAForumModeratorMayOnlyApplyTheForumBan(): void
    {
        $access = $this->access([CorePermissions::USERS_BAN_FORUM]);

        self::assertTrue($access->mayBanAnything());
        self::assertTrue($access->mayApply(11), 'The forum ban');
        self::assertFalse($access->mayApply(1), 'The full block');
        self::assertFalse($access->mayApply(15), 'The library ban');
    }

    public function testTheGeneralPermissionCoversTheBansThatAreNotOfOneSection(): void
    {
        $access = $this->access([CorePermissions::USERS_BAN_MANAGE]);

        foreach ([1, 3, 10, 13] as $banType) {
            self::assertTrue($access->mayApply($banType), 'Ban type ' . $banType);
        }

        self::assertFalse($access->mayApply(11), 'The forum has a permission of its own');
    }

    /**
     * The numbers 12, 14 and 16 were the sections of the access levels nobody documented. They
     * are offered by no form, so a request carrying one arrived by hand.
     */
    public function testAKindOfBanNobodyDeclaredIsRefused(): void
    {
        $access = $this->access([CorePermissions::USERS_BAN_MANAGE]);

        self::assertFalse($access->mayApply(0));
        self::assertFalse($access->mayApply(16));
    }

    /**
     * A moderator banned from the forum keeps the permission to ban from it, and would otherwise
     * go on moderating the forum through the profiles of its members.
     */
    public function testNobodyAppliesTheBanTheyAreUnder(): void
    {
        Capsule::table('cms_ban_users')->insert(
            ['user_id' => self::VISITOR, 'ban_time' => time() + 3600, 'ban_type' => 11, 'ban_reason' => 'Spam']
        );
        $access = $this->access([CorePermissions::USERS_BAN_FORUM]);

        self::assertFalse($access->mayApply(11));
        self::assertFalse($access->mayBanAnything());
    }

    public function testAVisitorWithoutAnyOfThePermissionsMayNotBanAtAll(): void
    {
        $access = $this->access([]);

        self::assertFalse($access->mayBanAnything());
        self::assertFalse($access->mayApply(1));
    }

    /**
     * @param list<string> $granted
     */
    private function access(array $granted): BanAccess
    {
        $profile = new User();
        $profile->forceFill(['id' => self::VISITOR]);

        return new BanAccess(
            $this->accessChecker($granted),
            new RoleLevels(new FakeRoleRepository()),
            CurrentUserFactory::for(IdentityFactory::user(id: self::VISITOR), $profile)
        );
    }

    /**
     * @param list<string> $granted
     */
    private function accessChecker(array $granted): AccessCheckerInterface
    {
        return new class ($granted) implements AccessCheckerInterface {
            /**
             * @param list<string> $granted
             */
            public function __construct(private readonly array $granted)
            {
            }

            public function allows(string $permission, mixed $subject = null): bool
            {
                return in_array($permission, $this->granted, true);
            }

            public function allowsFor(Identity $identity, string $permission, mixed $subject = null): bool
            {
                return $this->allows($permission, $subject);
            }
        };
    }
}
