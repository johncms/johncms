<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Application\DTO;

final readonly class ConsentDTO
{
    public function __construct(
        public string $context,
        public string $language,
        public string $title,
        public string $text,
        public string $version,
        public bool $isRequired,
        public bool $isActive,
    ) {
    }
}
