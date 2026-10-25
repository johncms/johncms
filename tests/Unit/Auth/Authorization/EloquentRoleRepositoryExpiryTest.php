<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Authorization;

use Johncms\Auth\Authorization\Role;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentRoleRepository;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\RunsMigrations;

/**
 * A grant with an end date counts until that moment and not a second after: the moment itself is
 * already past it. Whatever reads the grants — the permissions of a visitor, their level, the
 * holders of a role — reads them through this repository, so every way of asking is checked here.
 */
final class EloquentRoleRepositoryExpiryTest extends TestCase
{
    use BootsInMemoryDatabase;
    use RunsMigrations;

    private const NOW = 1_800_000_000;

    private const FOREVER = 1;
    private const UNTIL_LATER = 2;
    private const UNTIL_NOW = 3;
    private const RAN_OUT = 4;

    private EloquentRoleRepository $roles;

    private Role $role;

    protected function setUp(): void
    {
        $this->bootDatabase();
        $this->migrate('system', 'initial_auth_schema');

        $this->roles = new EloquentRoleRepository();
        $this->role = $this->roles->create('moderator', 'Moderator', 30, self::NOW);

        $this->roles->grant(self::FOREVER, $this->role->id, null, self::NOW - 100);
        $this->roles->grant(self::UNTIL_LATER, $this->role->id, null, self::NOW - 100, self::NOW + 1);
        $this->roles->grant(self::UNTIL_NOW, $this->role->id, null, self::NOW - 100, self::NOW);
        $this->roles->grant(self::RAN_OUT, $this->role->id, null, self::NOW - 100, self::NOW - 1);
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testARoleIsHeldUntilItsGrantRunsOut(): void
    {
        self::assertSame(['moderator'], $this->slugsGrantedTo(self::FOREVER));
        self::assertSame(['moderator'], $this->slugsGrantedTo(self::UNTIL_LATER));
        self::assertSame([], $this->slugsGrantedTo(self::UNTIL_NOW), 'The moment of the end is past it.');
        self::assertSame([], $this->slugsGrantedTo(self::RAN_OUT));
    }

    public function testTheRolesOfAVisitorLeaveOutAGrantThatRanOut(): void
    {
        $default = $this->roles->create('user', 'User', 10, self::NOW);
        $default->is_default = true;
        $default->save();

        self::assertSame(['moderator', 'user'], $this->slugsOf($this->roles->forUser(self::UNTIL_LATER, self::NOW)->all()));
        self::assertSame(['user'], $this->slugsOf($this->roles->forUser(self::RAN_OUT, self::NOW)->all()));
    }

    public function testTheLevelsOfAPageOfAccountsLeaveOutAGrantThatRanOut(): void
    {
        $levels = $this->roles->grantedLevelsFor([self::FOREVER, self::UNTIL_LATER, self::UNTIL_NOW, self::RAN_OUT], self::NOW);

        self::assertSame([self::FOREVER => 30, self::UNTIL_LATER => 30], array_map('intval', $levels));
    }

    public function testTheRolesOfAPageOfAccountsLeaveOutAGrantThatRanOut(): void
    {
        $granted = $this->roles->grantedRolesFor([self::FOREVER, self::UNTIL_LATER, self::UNTIL_NOW, self::RAN_OUT], self::NOW);

        self::assertSame([self::FOREVER, self::UNTIL_LATER], array_keys($granted));
    }

    public function testTheHoldersOfARoleLeaveOutAGrantThatRanOut(): void
    {
        self::assertSame(2, (int) $this->roles->holderCounts(self::NOW)[$this->role->id]);
    }

    public function testAGrantThatRanOutIsStillOnRecord(): void
    {
        self::assertSame([$this->role->id => self::NOW - 1], array_map('intval', $this->roles->grantsFor(self::RAN_OUT)));
    }

    /**
     * @return list<string>
     */
    private function slugsGrantedTo(int $userId): array
    {
        return $this->slugsOf($this->roles->grantedTo($userId, self::NOW)->all());
    }

    /**
     * @param array<int, Role> $roles
     * @return list<string>
     */
    private function slugsOf(array $roles): array
    {
        $slugs = array_map(static fn (Role $role): string => $role->slug, $roles);
        sort($slugs);

        return $slugs;
    }
}
