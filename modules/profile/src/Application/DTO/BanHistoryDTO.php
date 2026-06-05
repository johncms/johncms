<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\DTO;

final readonly class BanHistoryDTO
{
    /**
     * @param list<array<string, mixed>> $items
     */
    public function __construct(
        public string $userName,
        public array $items,
        public int $total,
        public string $pagination,
        public ?string $clearHistoryUrl,
        public string $backUrl,
    ) {
    }
}
