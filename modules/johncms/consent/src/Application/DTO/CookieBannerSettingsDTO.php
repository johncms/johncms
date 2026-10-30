<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Application\DTO;

final readonly class CookieBannerSettingsDTO
{
    /**
     * @param array<string, string> $texts  Banner text per language code.
     * @param bool                  $reshow Ask every visitor again: the version of the consent
     *                                      goes up, and an acceptance of the old one no longer
     *                                      counts.
     */
    public function __construct(
        public int $enabled,
        public array $texts,
        public bool $reshow = false,
    ) {
    }
}
