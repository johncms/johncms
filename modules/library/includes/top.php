<?php

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

defined('_IN_JOHNCMS') || die('Error: restricted access');

use Library\Hashtags;
use Library\Rating;

$sort = isset($_GET['sort']) && $_GET['sort'] === 'rating'
    ? 'rating'
    : (isset($_GET['sort']) && $_GET['sort'] === 'comm'
        ? 'comm'
        : 'read'
    );

$menu[] = $sort === 'read' ? '<strong>' . _t('Most readings') . '</strong>' : '<a href="?act=top&amp;sort=read">' . _t('Most readings') . '</a> ';
$menu[] = $sort === 'rating' ? '<strong>' . _t('By rating') . '</strong>' : '<a href="?act=top&amp;sort=rating">' . _t('By rating') . '</a> ';
$menu[] = $sort === 'comm' ? '<strong>' . _t('By comments') . '</strong>' : '<a href="?act=top&amp;sort=comm">' . _t('By comments') . '</a>';

$menu = implode(' | ', $menu);

$field = $sort === 'comm' ? '`comm_count`' : '`count_views`';

if ($sort === 'read' || $sort === 'comm') {
    $total = $db->query('SELECT COUNT(*) FROM `library_texts` WHERE ' . $field . ' > 0 ORDER BY ' . $field . ' DESC LIMIT 20')->fetchColumn();
} else {
    $total = $db->query('SELECT COUNT(*) AS `cnt`, AVG(`point`) AS `avg` FROM `cms_library_rating` GROUP BY `st_id` ORDER BY `avg` DESC, `cnt` DESC LIMIT 20')->fetchColumn(0);
}

if ($total) {
    if ($sort === 'read' || $sort === 'comm') {
        $req = $db->query(
            'SELECT `id`, `name`, `time`, `uploader`, `uploader_id`, `count_views`, `cat_id`, `comments`, `comm_count`, `announce` FROM `library_texts`
            WHERE ' . $field . ' > 0
            ORDER BY ' . $field . ' DESC
            LIMIT 20'
        );
    } else {
        $req = $db->query(
            'SELECT `library_texts`.*, COUNT(*) AS `cnt`, AVG(`point`) AS `avg` FROM `cms_library_rating`
            JOIN `library_texts` ON `cms_library_rating`.`st_id` = `library_texts`.`id`
            GROUP BY `cms_library_rating`.`st_id`
            ORDER BY `avg` DESC, `cnt` DESC
            LIMIT 20'
        );
    }
}

echo $view->render(
    'library::top',
    [
        'total' => $total,
        'menu'  => $menu,
        'list'  =>
            static function () use ($req, $tools, $db) {
                while ($res = $req->fetch()) {
                    $res['cover'] = file_exists(UPLOAD_PATH . 'library/images/small/' . $res['id'] . '.png');

                    $obj = new Hashtags($res['id']);
                    $res['tags'] = $obj->getAllStatTags() ? $obj->getAllStatTags(1) : null;

                    $rate = new Rating($res['id']);
                    $res['ratingView'] = $rate->viewRate(1);

                    $uploader = $res['uploader_id']
                        ? '<a href="' . di('config')['johncms']['homeurl'] . '/profile/?user=' . $res['uploader_id'] . '">' . $tools->checkout($res['uploader']) . '</a>'
                        : $tools->checkout($res['uploader']);

                    $res['who'] = $uploader . ' (' . $tools->displayDate($res['time']) . ')';

                    $res['cat_name'] = $tools->checkout($db->query('SELECT `name` FROM `library_cats` WHERE `id` = ' . $res['cat_id'])->fetchColumn());

                    $res['name'] = $tools->checkout($res['name']);
                    $res['announce'] = $tools->checkout($res['announce'], 0, 0);

                    yield $res;
                }
            },
    ]
);
