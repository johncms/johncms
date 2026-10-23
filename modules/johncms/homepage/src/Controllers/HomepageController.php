<?php

declare(strict_types=1);

namespace Johncms\Modules\Homepage\Controllers;

use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\NavChain;

final readonly class HomepageController
{
    public function __construct(
        private NavChain $navChain,
    ) {
    }

    public function __invoke(Request $request): ViewResponse
    {
        // Marks the request, not the process: Ads and Counters read the flag from here to pick
        // what belongs on the home page. It used to be the _IS_HOMEPAGE constant, which cannot be
        // unset — under a long-running runtime every page served after the home page inherited it.
        $request->attributes->set('is_homepage', true);
        $this->navChain->showHomePage(false);

        $config = config('johncms');
        $newsConfig = config('news');

        $news = [];
        if ($newsConfig['homepage_show']) {
            $query = (new NewsArticle())->withCount('comments')->withSum('votes', 'vote')->active();
            if ($newsConfig['homepage_days'] > 0) {
                $query->lastDays($newsConfig['homepage_days']);
            }
            $news = $query->limit($newsConfig['homepage_quantity'])
                ->orderByDesc('active_from')
                ->orderByDesc('id')
                ->get();
        }

        return new ViewResponse(
            '@homepage/public/index.twig',
            [
                'canonical'   => $config['homeurl'] . '/',
                'title'       => $config['meta_title'] ?? '',
                'keywords'    => $config['meta_key'],
                'description' => $config['meta_desc'],
                'news'        => $news,
            ]
        );
    }
}
