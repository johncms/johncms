<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\DTO;

final readonly class ConversationResultDTO
{
    /**
     * @param \Illuminate\Support\Collection<int, MessageItemDTO> $items
     */
    public function __construct(
        public \Illuminate\Support\Collection $items,
        public int $total,
        public string $pagination,
        public string $backUrl,
        public string $clearUrl,
        public ?string $formAction,
        public bool $showNickInput,
        public string $nick,
    ) {
    }
}
