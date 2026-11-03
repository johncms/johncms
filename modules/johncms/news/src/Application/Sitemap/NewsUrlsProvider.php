<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Sitemap;

use Johncms\Modules\News\Application\Collections\NewsCollectionAvailability;
use Johncms\Modules\News\Application\Collections\NewsReadServiceInterface;
use Johncms\Sitemap\SitemapUrlProviderInterface;

final readonly class NewsUrlsProvider implements SitemapUrlProviderInterface
{
    public function __construct(private NewsReadServiceInterface $news, private NewsCollectionAvailability $availability)
    {
    }

    public function groupName(): string
    {
        return 'news';
    }

    public function getEntries(string $homeUrl): iterable
    {
        if ($this->availability->ready()) {
            yield from $this->news->sitemap($homeUrl);
        }
    }
}
