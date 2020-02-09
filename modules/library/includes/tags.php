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

$obj = new Hashtags(0);

$title = __('Tags');
$nav_chain->add($title);

if (isset($_GET['tag'])) {
    $tag = urldecode($_GET['tag']);

    if ($obj->getAllTagStats($tag)) {
        $total = count($obj->getAllTagStats($tag));
        $page = $page >= ceil($total / $user->config->kmess) ? ceil($total / $user->config->kmess) : $page;
        $start = $page === 1 ? 0 : ($page - 1) * $user->config->kmess;

        $pagination = $tools->displayPagination('?act=tags&amp;tag=' . urlencode($tag) . '&amp;', $start, $total, $user->config->kmess);

        $list = [];

        foreach (new LimitIterator(new ArrayIterator($obj->getAllTagStats($tag)), (int) $start, $user->config->kmess) as $txt) {
            $query = $db->query('SELECT `id`, `name`, `time`, `uploader`, `uploader_id`, `count_views`, `comm_count`, `comments` FROM `library_texts` WHERE `id` = ' . $txt . ' LIMIT 1');
            if ($query->rowCount()) {
                $res = $query->fetch();

                $res['cover'] = file_exists(UPLOAD_PATH . 'library/images/small/' . $res['id'] . '.png');
                $res['name'] = $tools->checkout($res['name']);
                $res['text'] = $tools->checkout($db->query('SELECT SUBSTRING(`text`, 1 , 200) FROM `library_texts` WHERE `id`=' . $res['id'])->fetchColumn(), 0, 2);
                $uploader = $res['uploader_id']
                    ? '<a href="' . di('config')['johncms']['homeurl'] . '/profile/?user=' . $res['uploader_id'] . '">' . $tools->checkout($res['uploader']) . '</a>'
                    : $tools->checkout($res['uploader']);

                $res['who'] = $uploader . ' (' . $tools->displayDate($res['time']) . ')';

                $obj = new Hashtags($res['id']);
                $res['tags'] = $obj->getAllStatTags() ? $obj->getAllStatTags(1) : null;
                $list[] = $res;
            }
            #$total = count($res);
        }
        echo $view->render(
            'library::tags',
            [
                'title'      => $title,
                'page_title' => $title,
                'total'      => $total,
                'pagination' => $pagination,
                'list'       => $list,
            ]
        );
    } else {
        echo $view->render(
            'system::pages/result',
            [
                'title'    => $title,
                'type'     => 'alert-info',
                'message'  => __('The list is empty'),
                'back_url' => '/library/',
            ]
        );
    }
} else {
    Library\Utils::redir404();
}
