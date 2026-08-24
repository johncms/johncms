<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\DTO;

final readonly class ContactItemDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public int $countMessage,
        public int $newCountMessage,
        public bool $userIsOnline,
        public array $buttons,
    ) {
    }
}
