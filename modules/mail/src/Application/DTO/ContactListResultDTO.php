<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\DTO;

use Illuminate\Support\Collection;

final readonly class ContactListResultDTO
{
    /**
     * @param Collection<int, ContactItemDTO> $items
     * @param array<string, array{name: string, url: string, active: bool}> $filters
     */
    public function __construct(
        public Collection $items,
        public array $filters,
        public string $backUrl,
    ) {
    }
}
