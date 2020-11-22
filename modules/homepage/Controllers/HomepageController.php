<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Homepage\Controllers;

use Johncms\Controller\BaseController;
use Johncms\Counters;
use News\Models\NewsArticle;

class HomepageController extends BaseController
{
    protected $module_name = 'homepage';

    public function index(): string
    {
        define('_IS_HOMEPAGE', 1);
        $this->nav_chain->showHomePage(false);

        $config = di('config')['johncms'];
        $this->render->addData(
            [
                'title'       => $config['meta_title'] ?? '',
                'keywords'    => $config['meta_key'],
                'description' => $config['meta_desc'],
            ]
        );

        $data = [];
        if ($config['news']['view'] > 0) {
            $news = (new NewsArticle())->active()->lastDays($config['news']['days'])->limit($config['news']['quantity'])->get();
        }

        $data['news'] = $news ?? [];
        // TODO: Если приживется, объединить со счетчиками в меню для избежания лишних запросов
        /** @var Counters $counters */
        $counters = di('counters');
        $count['forum'] = $counters->forumCounters();
        $count['guestbook'] = $counters->guestbookCounters();
        $count['downloads'] = $counters->downloadsCounters();
        $count['library'] = $counters->libraryCounters();
        $count['users'] = $counters->usersCounters();
        $count['news'] = [
            'new' => (new NewsArticle())->active()->lastDays($config['news']['days'])->count(),
        ];
        $data['counters'] = $count;

        return $this->render->render('homepage::index', ['data' => $data]);
    }
}
