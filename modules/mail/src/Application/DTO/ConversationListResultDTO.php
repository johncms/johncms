<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\DTO;

final readonly class ConversationListResultDTO
{
    /**
     * @param \Illuminate\Support\Collection<int, ConversationItemDTO> $items
     */
    public function __construct(
        public \Illuminate\Support\Collection $items,
        public int $total,
        public string $pagination,
        public string $backUrl,
    ) {
    }
}
