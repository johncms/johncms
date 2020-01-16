<?php

/**
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

$total = $db->query("SELECT COUNT(*) FROM `library_texts` WHERE `time` > '" . (time() - 259200) . "' AND `premod` = 1")->fetchColumn();
$req = $db->query(
    "SELECT `id`, `name`, `time`, `uploader`, `uploader_id`, `count_views`, `comments`, `comm_count`, `cat_id`, `announce` FROM `library_texts`
    WHERE `time` > '" . (time() - 259200) . "'
    AND `premod` = 1
    ORDER BY `time` DESC
    LIMIT " . $start . ', ' . $user->config->kmess
);

$title = __('New Articles');
$nav_chain->add($title);

echo $view->render(
    'library::new',
    [
        'title'      => $title,
        'page_title' => $title,
        'pagination' => $tools->displayPagination('?act=new&amp;', $start, $total, $user->config->kmess),
        'total'      => $total,
        'list'       =>
            static function () use ($req, $tools, $config, $db) {
                while ($res = $req->fetch()) {
                    $res['cover'] = file_exists(UPLOAD_PATH . 'library/images/small/' . $res['id'] . '.png');

                    $obj = new Hashtags($res['id']);
                    $res['tags'] = $obj->getAllStatTags() ? $obj->getAllStatTags(1) : null;

                    $rate = new Rating($res['id']);
                    $res['ratingView'] = $rate->viewRate(1);

                    $uploader = $res['uploader_id']
                        ? '<a href="' . $config['homeurl'] . '/profile/?user=' . $res['uploader_id'] . '">' . $tools->checkout($res['uploader']) . '</a>'
                        : $tools->checkout($res['uploader']);
                    $res['who'] = $uploader . ' (' . $tools->displayDate($res['time']) . ')';

                    $res['name'] = $tools->checkout($res['name']);
                    $res['announce'] = $tools->checkout($res['announce'], 0, 0);

                    $catalog = $db->query('SELECT `id`, `name` FROM `library_cats` WHERE `id` = ' . $res['cat_id'] . ' LIMIT 1')->fetch();
                    $res['catalog_name'] = $tools->checkout($catalog['name']);

                    yield $res;
                }
            },
    ]
);
