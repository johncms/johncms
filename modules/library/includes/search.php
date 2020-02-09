<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Library\Utils;

defined('_IN_JOHNCMS') || die('Error: restricted access');

// Принимаем данные, выводим форму поиска
$search_post = isset($_POST['search']) ? trim($_POST['search']) : false;
$search_get = isset($_GET['search']) ? rawurldecode(trim($_GET['search'])) : false;
$search = $search_post ?? $search_get;
$search = $search ? $tools->checkout($search) : false;
$search_t = isset($_REQUEST['t']) ? 'checked="checked"' : '';
$title = __('Search');
$nav_chain->add($title);

$total = false;
$list = false;

$error = false;

if ($search && (mb_strlen($search) < 4 || mb_strlen($search) > 64)) {
    $error = true;
}

if ($search && ! $error) {
    /** @var PDO $db */
    $db = di(PDO::class);

    $array = explode(' ', $search);
    #$count = count($array);
    $query = $db->quote($search);

    $total = $db->query(
        'SELECT COUNT(*) FROM `library_texts`
        WHERE MATCH (`' . ($search_t ? 'name' : 'text') . '`) AGAINST (' . $query . ' IN BOOLEAN MODE)'
    )->fetchColumn();

    if ($total) {
        $req = $db->query(
            'SELECT *, MATCH (`' . ($search_t ? 'name' : 'text') . '`) AGAINST (' . $query . ' IN BOOLEAN MODE) AS `rel`
            FROM `library_texts`
            WHERE MATCH (`' . ($search_t ? 'name' : 'text') . '`) AGAINST (' . $query . ' IN BOOLEAN MODE)
            ORDER BY `rel` DESC
            LIMIT ' . $start . ', ' . $user->config->kmess
        );

        $list = [];
        while ($res = $req->fetch()) {
            foreach ($array as $srch) {
                if (($pos = mb_stripos($res['text'], str_replace('*', '', $srch))) !== false) {
                    break;
                }
            }
            if (! isset($pos) || $pos < 100) {
                $pos = 100;
            }
            $res['name'] = $tools->checkout($res['name']);
            $res['text'] = $tools->checkout(mb_substr($res['text'], ($pos - 100), 400), 1);
            $res['time'] = $tools->displayDate($res['time']);
            $res['author'] = $res['uploader_id']
                ? '<a href="' . di('config')['johncms']['homeurl'] . '/profile/?user=' . $res['uploader_id'] . '">' . $tools->checkout($res['uploader']) . '</a>'
                : $tools->checkout($res['uploader']);
            foreach ($array as $val) {
                if ($search_t) {
                    $res['name'] = Utils::replaceKeywords($val, $res['name']);
                } else {
                    $res['text'] = Utils::replaceKeywords($val, $res['text']);
                }
            }
            $list[] = $res;
        }
    }
}

echo $view->render(
    'library::search',
    [
        'title'      => $title,
        'page_title' => $title,
        'pagination' => $tools->displayPagination('?act=search&amp;' . ($search_t ? 't=1&amp;' : '') . 'search=' . urlencode((string) $search) . '&amp;', $start, $total, $user->config->kmess),
        'total'      => $total,
        'search_t'   => $search_t,
        'search'     => $search,
        'list'       => $list,
    ]
);
