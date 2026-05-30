<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\DTO;

final readonly class IpHistoryDTO
{
    /**
     * @param array<int, array{ip: string, search_url: string, display_date: string}> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public string $pagination,
        public string $backUrl,
        public string $profileName,
    ) {
    }
}
