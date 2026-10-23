<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Homepage;

use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\View\Homepage\HomepageBlock;
use Johncms\View\Homepage\HomepageBlockProviderInterface;

/**
 * The latest articles on the home page, as many and as fresh as the settings of the news ask for;
 * no block when they switch it off or there is nothing to show.
 */
final readonly class LatestNewsBlockProvider implements HomepageBlockProviderInterface
{
    public function homepageBlocks(): iterable
    {
        $settings = config('news');

        if (! $settings['homepage_show']) {
            return;
        }

        $query = NewsArticle::query()->withCount('comments')->withSum('votes', 'vote')->scopes(['active']);

        if ($settings['homepage_days'] > 0) {
            $query->scopes(['lastDays' => [$settings['homepage_days']]]);
        }

        $articles = $query->limit($settings['homepage_quantity'])
            ->orderByDesc('active_from')
            ->orderByDesc('id')
            ->get();

        if ($articles->isNotEmpty()) {
            yield new HomepageBlock('@news/homepage/latest.twig', ['news' => $articles], weight: 10);
        }
    }
}
