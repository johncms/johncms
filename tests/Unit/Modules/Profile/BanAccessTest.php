<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Profile;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Identity;
use Johncms\Modules\Profile\Application\Access\BanAccess;
use Johncms\Modules\Profile\Application\Services\ProfilePermissions;
use PHPUnit\Framework\TestCase;

final class BanAccessTest extends TestCase
{
    public function testAForumModeratorMayOnlyApplyTheForumBan(): void
    {
        $access = new BanAccess($this->accessChecker([ProfilePermissions::BAN_FORUM]));

        self::assertTrue($access->mayBanAnything());
        self::assertTrue($access->mayApply(11), 'The forum ban');
        self::assertFalse($access->mayApply(1), 'The full block');
        self::assertFalse($access->mayApply(15), 'The library ban');
    }

    public function testTheGeneralPermissionCoversTheBansThatAreNotOfOneSection(): void
    {
        $access = new BanAccess($this->accessChecker([ProfilePermissions::BAN_MANAGE]));

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
        $access = new BanAccess($this->accessChecker([ProfilePermissions::BAN_MANAGE]));

        self::assertFalse($access->mayApply(0));
        self::assertFalse($access->mayApply(16));
    }

    public function testAVisitorWithoutAnyOfThePermissionsMayNotBanAtAll(): void
    {
        $access = new BanAccess($this->accessChecker([]));

        self::assertFalse($access->mayBanAnything());
        self::assertFalse($access->mayApply(1));
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
