<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

/**
 * The roles of one account, as the screen shows them.
 */
final readonly class UserRolesDTO
{
    /**
     * @param list<UserRoleRowDTO> $rows
     * @param string|null          $defaultRole The role everybody signed in holds without a row of
     *                                          their own; shown as a note rather than a checkbox.
     */
    public function __construct(
        public int $userId,
        public string $userName,
        public array $rows,
        public ?string $defaultRole,
    ) {
    }
}
