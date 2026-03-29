<?php

declare(strict_types=1);

namespace Johncms\Sitemap;

interface SitemapUrlProviderInterface
{
    public function groupName(): string;

    /**
     * @return iterable<SitemapUrlEntry>
     */
    public function getEntries(string $homeUrl): iterable;
}
