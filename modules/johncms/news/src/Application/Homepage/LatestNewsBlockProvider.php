<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Homepage;

use Johncms\Modules\News\Application\Collections\NewsCollectionAvailability;
use Johncms\Modules\News\Application\Collections\NewsReadServiceInterface;
use Johncms\View\Homepage\HomepageBlock;
use Johncms\View\Homepage\HomepageBlockProviderInterface;

final readonly class LatestNewsBlockProvider implements HomepageBlockProviderInterface
{
    public function __construct(private NewsReadServiceInterface $news, private NewsCollectionAvailability $availability)
    {
    }

    public function homepageBlocks(): iterable
    {
        if (! $this->availability->ready() || empty(config('news')['homepage_show'])) {
            return;
        }
        yield new HomepageBlock('@news/homepage/latest.twig', ['news' => array_map(static fn ($article): array => $article->toTemplate(), $this->news->homepage())], weight: 10);
    }
}
