<?php

declare(strict_types=1);

namespace Johncms\Sitemap;

final readonly class SitemapUrlEntry
{
    public function __construct(
        public string $loc,
        public ?string $lastmod = null,
    ) {
    }
}
