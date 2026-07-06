<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

/**
 * A public listing row. The detail URL is built in the controller from the
 * current path and the item code.
 */
final readonly class PublicItemDTO
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $previewText,
    ) {
    }
}
