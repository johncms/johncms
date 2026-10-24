<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\ValueObjects;

use Johncms\Modules\Admin\Domain\Enums\SortDirection;
use Johncms\Modules\Admin\Domain\Enums\UserListActivity;
use Johncms\Modules\Admin\Domain\Enums\UserListRegistered;
use Johncms\Modules\Admin\Domain\Enums\UserListSort;
use Johncms\Modules\Admin\Domain\Enums\UserListStatus;

/**
 * What the list of users is narrowed to and how it is ordered.
 *
 * The role is either one role (roleId) or the staff as a whole (staff): anybody holding a role
 * granted to them that has not run out.
 */
final readonly class UserListFilter
{
    public function __construct(
        public string $search = '',
        public UserListStatus $status = UserListStatus::ALL,
        public bool $staff = false,
        public ?int $roleId = null,
        public UserListActivity $activity = UserListActivity::ANY,
        public UserListRegistered $registered = UserListRegistered::ANY,
        public UserListSort $sort = UserListSort::ID,
        public SortDirection $direction = SortDirection::DESC,
    ) {
    }

    public function withSearch(string $search): self
    {
        return new self($search, $this->status, $this->staff, $this->roleId, $this->activity, $this->registered, $this->sort, $this->direction);
    }

    public function withStatus(UserListStatus $status): self
    {
        return new self($this->search, $status, $this->staff, $this->roleId, $this->activity, $this->registered, $this->sort, $this->direction);
    }

    /**
     * No role (both off), the staff as a whole, or one role.
     */
    public function withRole(bool $staff, ?int $roleId): self
    {
        return new self($this->search, $this->status, $staff, $roleId, $this->activity, $this->registered, $this->sort, $this->direction);
    }

    public function withActivity(UserListActivity $activity): self
    {
        return new self($this->search, $this->status, $this->staff, $this->roleId, $activity, $this->registered, $this->sort, $this->direction);
    }

    public function withRegistered(UserListRegistered $registered): self
    {
        return new self($this->search, $this->status, $this->staff, $this->roleId, $this->activity, $registered, $this->sort, $this->direction);
    }

    public function withSort(UserListSort $sort, SortDirection $direction): self
    {
        return new self($this->search, $this->status, $this->staff, $this->roleId, $this->activity, $this->registered, $sort, $direction);
    }
}
