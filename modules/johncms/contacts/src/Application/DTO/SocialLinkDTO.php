<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Application\DTO;

final readonly class SocialLinkDTO
{
    public function __construct(
        public string $title,
        public string $url,
    ) {
    }
}
