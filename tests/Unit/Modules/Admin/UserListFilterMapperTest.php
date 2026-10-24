<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin;

use Johncms\Modules\Admin\Application\Services\UserListFilterMapper;
use Johncms\Modules\Admin\Domain\Enums\SortDirection;
use Johncms\Modules\Admin\Domain\Enums\UserListActivity;
use Johncms\Modules\Admin\Domain\Enums\UserListSort;
use Johncms\Modules\Admin\Domain\Enums\UserListStatus;
use PHPUnit\Framework\TestCase;

/**
 * The links of the list of users are built from the filter, so they have to read back as the same
 * filter and leave out what it holds by default.
 */
final class UserListFilterMapperTest extends TestCase
{
    public function testAFilterReadsBackFromTheQueryItWritesTo(): void
    {
        $mapper = new UserListFilterMapper();
        $query = ['q' => 'gandalf', 'status' => 'banned', 'role' => '5', 'activity' => 'week', 'registered' => 'month', 'sort' => 'karma', 'dir' => 'asc'];

        self::assertSame($query, $mapper->toQuery($mapper->fromQuery($query)));
    }

    public function testDefaultsAreLeftOutOfTheAddress(): void
    {
        $mapper = new UserListFilterMapper();

        self::assertSame('/admin/users', $mapper->url('/admin/users', $mapper->fromQuery([])));
        // A column ordered its own way first needs no direction in the address.
        self::assertSame(['sort' => 'nick'], $mapper->toQuery($mapper->fromQuery(['sort' => 'nick', 'dir' => 'asc'])));
    }

    public function testValuesItDoesNotKnowReadAsTheDefaults(): void
    {
        $filter = (new UserListFilterMapper())->fromQuery(['status' => 'deleted', 'role' => '-3', 'activity' => ['online'], 'sort' => 'password']);

        self::assertSame(UserListStatus::ALL, $filter->status);
        self::assertNull($filter->roleId);
        self::assertFalse($filter->staff);
        self::assertSame(UserListActivity::ANY, $filter->activity);
        self::assertSame(UserListSort::ID, $filter->sort);
        self::assertSame(SortDirection::DESC, $filter->direction);
    }

    public function testTheStaffIsARoleOfItsOwn(): void
    {
        $filter = (new UserListFilterMapper())->fromQuery(['role' => 'staff']);

        self::assertTrue($filter->staff);
        self::assertNull($filter->roleId);
    }
}
