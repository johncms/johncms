<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Authorization;

use Johncms\Auth\Authorization\RoleLevels;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeRoleRepository;
use Tests\Support\IdentityFactory;

final class RoleLevelsTest extends TestCase
{
    private FakeRoleRepository $roles;

    private RoleLevels $levels;

    protected function setUp(): void
    {
        $this->roles = new FakeRoleRepository();
        $this->roles->add('user', level: 10, isDefault: true);
        $this->roles->add('moderator', level: 30);
        $this->roles->add('administrator', level: 70);
        $this->levels = new RoleLevels($this->roles);
    }

    public function testTheHighestOfTheRolesTheVisitorHolds(): void
    {
        self::assertSame(0, $this->levels->highest(IdentityFactory::guest()));
        self::assertSame(
            30,
            $this->levels->highest(IdentityFactory::withRoles(['user', 'moderator']))
        );
    }

    /**
     * A listing asks about every author on the page at once. The answer has to match the one the
     * guard of the screen gives account by account, or a page shows buttons that lead to a 403.
     */
    public function testAWholePageOfAccountsIsAnsweredLikeEachOfThemAlone(): void
    {
        $this->roles->grantTo(1, ['administrator']);
        $this->roles->grantTo(2, ['moderator']);

        $levels = $this->levels->highestGrantedToMany([1, 2, 3]);

        self::assertSame([1 => 70, 2 => 30, 3 => 10], $levels);

        foreach ($levels as $userId => $level) {
            self::assertSame($this->levels->highestGrantedTo($userId), $level);
        }
    }

    public function testAskingAboutNobodyAsksTheRepositoryNothing(): void
    {
        self::assertSame([], $this->levels->highestGrantedToMany([]));
    }
}
