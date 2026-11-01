<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

final readonly class CollectionItemPatchDTO
{
    /**
     * @param array<string, mixed> $attributes Base form property names. Missing properties are preserved.
     * @param array<string, string|list<string>|null> $values Null clears a field; lists replace the whole list.
     */
    public function __construct(public array $attributes = [], public array $values = [])
    {
    }
}
