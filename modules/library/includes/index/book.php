<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Library\Hashtags;
use Library\Rating;
use Library\Tree;
use Library\Utils;

$res = $db->query('SELECT * FROM `library_texts` WHERE `id` = ' . $id)->fetch();

if (! $res['premod']) {
    Utils::redir404();
} else {
// Счетчик прочтений
    if (! isset($_SESSION['lib']) || (isset($_SESSION['lib']) && $_SESSION['lib'] !== $id)) {
        $_SESSION['lib'] = $id;
        $db->exec('UPDATE `library_texts` SET `count_views` = ' . ($res['count_views'] ? ++$res['count_views'] : 1) . ' WHERE `id` = ' . $id);
    }

// Запрашиваем выбранную статью из базы
    $symbols = 7000; // Это можно в настройку какую либо вынести

    $count_pages = ceil($db->query("SELECT CHAR_LENGTH(`text`) FROM `library_texts` WHERE `id`= '" . $id . "' LIMIT 1")->fetchColumn() / $symbols);

// Чтоб всегда последнюю страницу считал правильно
    $page = (int) ($page >= $count_pages ? $count_pages : $page);

    $offset = (int) ($page === 1 ? 1 : ($page - 1) * $symbols);
    $text = $db->query('SELECT SUBSTRING(`text`, ' . $offset . ', ' . ($symbols + 100) . ') FROM `library_texts` WHERE `id` = ' . $id)->fetchColumn();
    $tmp = mb_substr($text, $symbols, 100);

    $tags = null;
    $who = null;
    $ratingVote = null;
    $ratingView = null;
    $cover = null;

    if ($page === 1) {
        $obj = new Hashtags($res['id']);
        $tags = $obj->getAllStatTags() ? $obj->getAllStatTags(1) : null;

        $rate = new Rating($res['id']);
        $ratingVote = $user->isValid() ? $rate->printVote() : null;
        $ratingView = $rate->viewRate(1);

        $uploader = $res['uploader_id']
            ? '<a href="' . $config['homeurl'] . '/profile/?user=' . $res['uploader_id'] . '">' . $tools->checkout($res['uploader']) . '</a>'
            : $tools->checkout($res['uploader']);

        $who = $uploader . ' (' . $tools->displayDate($res['time']) . ')';
        $comments = $res['comments'];

        $cover = file_exists(UPLOAD_PATH . 'library/images/big/' . $id . '.png');

        $rating = $user->isValid() ? $rate->printVote() : null;
        $ratingView = $rate->viewRate(1);
    }

    $moderMenu = ($adm || ($db->query('SELECT `uploader_id` FROM `library_texts` WHERE `id` = ' . $id)->fetchColumn() === $user->id && $user->isValid()));

    $text = $tools->checkout(
        mb_substr(
            $text,
            ($page === 1 ? 0 : min(Utils::position($text, PHP_EOL), Utils::position($text, ' '))),
            (
            ($count_pages === 1 || $page === $count_pages)
                ? $symbols
                : $symbols + min(Utils::position($tmp, PHP_EOL), Utils::position($tmp, ' ')) - ($page === 1 ? 0 : min(Utils::position($text, PHP_EOL), Utils::position($text, ' ')))
            )
        ),
        1,
        1
    );
    $res['text'] = $tools->smilies($text, $user->rights ? 1 : 0);
    $res['name'] = $tools->checkout($res['name']);

    $dir_nav = new Tree($res['cat_id']);
    $dir_nav->processNavPanel();
    $dir_nav->printNavPanel();

    $catalog = $db->query('SELECT `id`, `name` FROM `library_cats` WHERE `id` = ' . $res['cat_id'] . ' LIMIT 1')->fetch();
    $catalog['name'] = $tools->checkout($catalog['name']);
    $nav_chain->add($page_title);

    echo $view->render(
        'library::book',
        [
            'title'       => $title,
            'page_title'  => $page_title ?? $title,
            'pagination'  => $tools->displayPagination('?id=' . $id . '&amp;', $page === 1 ? 0 : ($page - 1) * 1, $count_pages, 1),
            'catalog'     => $catalog,
            'moderMenu'   => $moderMenu,
            'count_pages' => $count_pages,
            'page'        => $page,
            'tags'        => $tags,
            'who'         => $who,
            'ratingVote'  => $ratingVote,
            'ratingView'  => $ratingView,
            'cover'       => $cover,
            'res'         => $res,
        ]
    );
}
