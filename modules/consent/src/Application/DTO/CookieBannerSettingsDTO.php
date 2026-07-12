<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Application\DTO;

final readonly class CookieBannerSettingsDTO
{
    /**
     * @param array<string, string> $texts Banner text per language code.
     */
    public function __construct(
        public int $enabled,
        public int $version,
        public array $texts,
    ) {
    }
}
