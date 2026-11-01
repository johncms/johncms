<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

final readonly class CollectionItemPageDTO
{
    /** @param list<CollectionItemDTO> $items */
    public function __construct(public array $items, public int $total, public int $limit, public int $offset)
    {
    }
}
