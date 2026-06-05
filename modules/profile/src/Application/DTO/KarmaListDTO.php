<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\DTO;

final readonly class KarmaListDTO
{
    /**
     * @param list<array<string, mixed>>          $items
     * @param array<string, array<string, mixed>> $filters
     */
    public function __construct(
        public array $items,
        public int $total,
        public string $pagination,
        public array $filters,
        public ?string $resetUrl,
        public string $backUrl,
    ) {
    }
}
