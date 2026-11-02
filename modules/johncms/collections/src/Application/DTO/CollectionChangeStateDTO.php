<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

final readonly class CollectionChangeStateDTO
{
    public function __construct(public ?int $containerId, public ?bool $active, public string $code)
    {
    }
}
