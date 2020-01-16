<?php

use Library\Hashtags;
use Library\Rating;

$total = $db->query('SELECT COUNT(*) FROM `library_texts` WHERE `premod` = 1 AND `cat_id` = ' . $id)->fetchColumn();
$start = (int) $page === 1 ? 0 : ($page - 1) * $user->config->kmess;

$req = $db->query(
    'SELECT `id`, `name`, `time`, `uploader`, `uploader_id`, `count_views`, `comm_count`, `comments`, `announce`
                            FROM `library_texts`
                            WHERE `premod` = 1 AND `cat_id` = ' . $id . '
                            ORDER BY `id` DESC LIMIT ' . $start . ', ' . $user->config->kmess
);

$moderMenu = (isset($id) && $user->isValid()) && ($adm || ($db->query('SELECT `user_add` FROM `library_cats` WHERE `id` = ' . $id)->fetchColumn() > 0));

echo $view->render(
    'library::booklist',
    [
        'title'      => $title,
        'page_title' => $page_title ?? $title,
        'pagination' => $tools->displayPagination('?do=dir&amp;id=' . $id . '&amp;', $start, $total, $user->config->kmess),
        'total'      => $total,
        'admin'      => $adm,
        'moderMenu'  => $moderMenu,
        'id'         => $id,
        'list'       =>
            static function () use ($req, $tools, $config) {
                while ($res = $req->fetch()) {
                    $res['cover'] = file_exists(UPLOAD_PATH . 'library/images/small/' . $res['id'] . '.png');

                    $obj = new Hashtags($res['id']);
                    $res['tags'] = $obj->getAllStatTags() ? $obj->getAllStatTags(1) : null;

                    $rate = new Rating($res['id']);
                    $res['ratingView'] = $rate->viewRate(1);

                    $uploader = $res['uploader_id']
                        ? '<a href="' . $config['homeurl'] . '/profile/?user=' . $res['uploader_id'] . '">' . $tools->checkout($res['uploader']) . '</a>'
                        : $tools->checkout($res['uploader']);

                    $res['who'] = $uploader . '&nbsp;(' . $tools->displayDate($res['time']) . ')';

                    $res['name'] = $tools->checkout($res['name']);
                    $res['announce'] = $tools->checkout($res['announce'], 0, 0);

                    yield $res;
                }
            },
    ]
);
