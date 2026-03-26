<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Application\DTO;

use Johncms\Users\User;

final readonly class CommunitySearchResultDTO
{
    /**
     * @param array<int, string> $errors
     * @param array<int, User> $list
     */
    public function __construct(
        public string $title,
        public string $pageTitle,
        public string $searchQuery,
        public array $errors,
        public int $total,
        public array $list,
        public string $pagination,
    ) {
    }
}
