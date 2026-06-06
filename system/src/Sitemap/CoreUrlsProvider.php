<?php

declare(strict_types=1);

namespace Johncms\Sitemap;

final class CoreUrlsProvider
{
    /**
     * @return iterable<SitemapUrlEntry>
     */
    public function getEntries(string $homeUrl): iterable
    {
        $paths = [
            '/',
            '/news/',
            '/forum/',
            '/community/',
            '/guestbook/',
            '/downloads/',
            '/library/',
            '/help/',
            '/album',
            '/online/',
        ];

        foreach ($paths as $path) {
            yield new SitemapUrlEntry($homeUrl . $path);
        }
    }
}
