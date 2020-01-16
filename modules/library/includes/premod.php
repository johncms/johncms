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

use Library\Tree;

$article = false;

if ($id && isset($_GET['yes'])) {
    $sql = 'UPDATE `library_texts` SET `premod` = 1 WHERE `id` = ' . $id;
    $article = $tools->checkout($db->query('SELECT `name` FROM `library_texts` WHERE `id` = ' . $id)->fetchColumn());
}

if (isset($_GET['all'])) {
    $sql = 'UPDATE `library_texts` SET `premod` = 1';
}

if (($id && isset($_GET['yes'])) || isset($_GET['all'])) {
    $db->exec($sql);
}

$total = $db->query('SELECT COUNT(*) FROM `library_texts` WHERE `premod` = 0')->fetchColumn();
$page = (int) $page >= ceil($total / $user->config->kmess) ? ceil($total / $user->config->kmess) : $page;
$start = $page === 1 ? 0 : ($page - 1) * $user->config->kmess;

if ($total) {
    $req = $db->query('SELECT `id`, `name`, `time`, `uploader`, `uploader_id`, `cat_id` FROM `library_texts` WHERE `premod` = 0 ORDER BY `time` DESC LIMIT ' . $start . ', ' . $user->config->kmess);
}
echo $view->render(
    'library::premod',
    [
        'pagination' => $tools->displayPagination('?act=premod&amp;', $start, $total, $user->config->kmess),
        'total'      => $total,
        'article'    => $article,
        'id'         => $id,
        'list'       =>
            static function () use ($req, $tools) {
                while ($res = $req->fetch()) {
                    $dir_nav = new Tree($res['cat_id']);
                    $dir_nav->processNavPanel();
                    $res['navPanel'] = $dir_nav->printNavPanel();
                    $res['cover'] = file_exists(UPLOAD_PATH . 'library/images/small/' . $res['id'] . '.png');
                    $res['name'] = $tools->checkout($res['name']);
                    $uploader = $res['uploader_id']
                        ? '<a href="' . di('config')['johncms']['homeurl'] . '/profile/?user=' . $res['uploader_id'] . '">' . $tools->checkout($res['uploader']) . '</a>'
                        : $tools->checkout($res['uploader']);
                    $res['who'] = $uploader . ' (' . $tools->displayDate($res['time']) . ')';

                    yield $res;
                }
            },
    ]
);
