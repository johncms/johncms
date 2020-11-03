<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Counters;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\NavChain;
use Johncms\System\i18n\Translator;

defined('_IN_JOHNCMS') || die('Error: restricted access');
define('_IS_HOMEPAGE', 1); // Пометка главной страницы

/**
 * @var Render $view
 * @var NavChain $nav_chain
 * @var Tools $tools
 */

$view = di(Render::class);
$news_config = di('config')['johncms']['news'];
$tools = di(Tools::class);
$nav_chain = di(NavChain::class);
$nav_chain->showHomePage(false);

// Устанавливаем мета теги keywords и description
$config = di('config')['johncms'];
$view->addData(
    [
        'title'       => $config['meta_title'] ?? '',
        'keywords'    => $config['meta_key'],
        'description' => $config['meta_desc'],
    ]
);

// Register Namespace for module templates
$view->addFolder('homepage', __DIR__ . '/templates/');

// Register the module languages domain and folder
di(Translator::class)->addTranslationDomain('homepage', __DIR__ . '/locale');

$data = [];
module_lib_loader('news');
if ($news_config['view'] > 0) {
    module_lib_loader('news');
    $news = (new \News\Models\NewsArticle())->active()->lastDays(1)->limit(6)->get();
    foreach ($news as $item) {
        $items[] = [
            'text'  => $item->preview_text_safe,
            'title' => $item->name,
        ];
    }
}

$data['news'] = $items ?? [];

// TODO: Если приживется, объединить со счетчиками в меню для избежания лишних запросов
/** @var Counters $counters */
$counters = di('counters');
$count['forum'] = $counters->forumCounters();
$count['guestbook'] = $counters->guestbookCounters();
$count['downloads'] = $counters->downloadsCounters();
$count['library'] = $counters->libraryCounters();
$count['users'] = $counters->usersCounters();
$count['news'] = [
    'new' => 0,
];

$data['counters'] = $count;

echo $view->render('homepage::index', ['data' => $data]);
