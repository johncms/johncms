<?php

declare(strict_types=1);

namespace Johncms\Modules\Homepage\Controllers;

use Johncms\Counters;
use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\Request;
use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\NavChain;
use Johncms\System\View\Render;

final readonly class HomepageController
{
    public function __construct(
        private ControllerContext $context,
        private NavChain $navChain,
        private Render $render,
    ) {
        $this->context->initModule('homepage');
    }

    public function __invoke(Request $request): string
    {
        // Marks the request, not the process: Ads and Counters read the flag from here to pick
        // what belongs on the home page. It used to be the _IS_HOMEPAGE constant, which cannot be
        // unset — under a long-running runtime every page served after the home page inherited it.
        $request->attributes->set('is_homepage', true);
        $this->navChain->showHomePage(false);

        $config = config('johncms');
        $news_config = config('news');
        $this->render->addData(
            [
                'canonical'   => (string) $config['homeurl'] . '/',
                'title'       => $config['meta_title'] ?? '',
                'keywords'    => $config['meta_key'],
                'description' => $config['meta_desc'],
            ]
        );

        $data = [];
        if ($news_config['homepage_show']) {
            $news = (new NewsArticle())->withCount('comments')->withSum('votes', 'vote')->active();
            if ($news_config['homepage_days'] > 0) {
                $news->lastDays($news_config['homepage_days']);
                $news_new_count = $news->count();
            }
            $news = $news->limit($news_config['homepage_quantity'])->orderByDesc('active_from')->orderByDesc('id')->get();
        }

        $data['news'] = $news ?? [];
        /** @var Counters $counters */
        $counters = di('counters');
        $count['forum'] = $counters->forumCounters();
        $count['guestbook'] = $counters->guestbookCounters();
        $count['downloads'] = $counters->downloadsCounters();
        $count['library'] = $counters->libraryCounters();
        $count['users'] = $counters->usersCounters();
        $count['news'] = [
            'new' => $news_new_count ?? 0,
        ];
        $data['counters'] = $count;

        return $this->render->render('homepage::index', ['data' => $data]);
    }
}
