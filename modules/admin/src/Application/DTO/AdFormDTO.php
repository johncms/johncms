<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

final readonly class AdFormDTO
{
    public function __construct(
        public string $link,
        public string $name,
        public string $color,
        public int $countLink,
        public int $day,
        public int $view,
        public int $type,
        public int $layout,
        public bool $directLink,
        public bool $bold,
        public bool $italic,
        public bool $underline,
    ) {
    }
}
