<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\DTO;

final readonly class ActivityDTO
{
    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function __construct(
        public string $itemType,
        public array $items,
        public string $profileName,
        public int $profileId,
    ) {
    }
}
