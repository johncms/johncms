<?php

declare(strict_types=1);

namespace Johncms\Modules\Notifications\Application\DTO;

final readonly class NotificationListResultDTO
{
    public function __construct(
        public array $systemNotifications,
        public array $items,
    ) {
    }
}
