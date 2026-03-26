<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Application\DTO;

use Johncms\Users\User;

final readonly class AdministrationUsersResultDTO
{
    /**
     * @param array<int, User> $list
     */
    public function __construct(
        public array $list,
        public int $total,
        public string $pagination,
        public string $title,
        public string $pageTitle,
    ) {
    }
}
