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
if ($news_config['view'] > 0) {
    $reqtime = $news_config['days'] ? time() - ($news_config['days'] * 86400) : 0;
    $req = $db->query(
        "SELECT * FROM `news` WHERE `time` > '${reqtime}' ORDER BY `time` DESC LIMIT " .
        $news_config['quantity']
    );

    if ($req->rowCount()) {
        $i = 0;
        $news = '';

        $items = [];
        while ($res = $req->fetch()) {
            $text = $res['text'];
            $moreLink = '';

            // Если текст больше заданного предела, обрезаем
            if (mb_strlen($text) > $news_config['size']) {
                $text = mb_substr($text, 0, $news_config['size']);
                $text = htmlentities($text, ENT_QUOTES, 'UTF-8') . '...';
            }

            $text = $tools->checkout($text, $news_config['breaks'] ? 1 : 2, $news_config['tags'] ? 1 : 2);

            if ($news_config['smileys']) {
                $text = $tools->smilies($text);
            }

            // Ссылка на каменты
            $comments_url = '';
            $comments_count = 0;

            if (! empty($res['kom']) && $news_config['view'] !== 2 && $news_config['kom'] > 0) {
                $res_mes = $db->query("SELECT * FROM `forum_topic` WHERE `id` = '" . $res['kom'] . "'");
                if ($mes = $res_mes->fetch()) {
                    $comments_count = $mes['post_count'] - 1;
                }
                if ($comments_count >= 0) {
                    $comments_url = '/forum/?type=topic&id=' . $res['kom'];
                }
            }

            $items[] = [
                'text'         => $news_config['view'] !== 2 ? $text : '',
                'title'        => $res['name'],
                'comments'     => $comments_count ?? 0,
                'comments_url' => $comments_url,
            ];
        }
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
$count['news'] = $counters->news();

$data['counters'] = $count;

echo $view->render('homepage::index', ['data' => $data]);
