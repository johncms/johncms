<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

final readonly class CollectionFileChoiceDTO
{
    public function __construct(
        public string $code,
        public string $label,
        public string $url,
        public int $size,
        public string $sizeLabel,
        public bool $image,
        public bool $inResults = true,
    ) {
    }
}
