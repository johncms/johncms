<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Downloads\Download;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 */

$id = isset($_REQUEST['id']) ? abs((int) ($_REQUEST['id'])) : 0;
$stag = isset($_REQUEST['stag']) ? abs((int) ($_REQUEST['stag'])) : 0;
$sven = isset($_REQUEST['sven']) ? abs((int) ($_REQUEST['sven'])) : 0;

// Поиск файлов
$search_post = isset($_POST['search']) ? trim($_POST['search']) : false;
$search_get = isset($_GET['search']) ? rawurldecode(trim($_GET['search'])) : '';
$catid = isset($_GET['catid']) ? rawurldecode(trim($_GET['catid'])) : null;
$search = $search_post ?: $search_get;

$nav_chain->add(__('Search'));

// Проверяем на коректность ввода
$error = false;

if ((! empty($search) && mb_strlen($search) < 2) || mb_strlen($search) > 64) {
    $error = __('Invalid file name length. Allowed a minimum of 3 and a maximum of 64 characters.');
}

if ($catid) {
	$catdir = $db->query("SELECT * FROM `download__category` WHERE `id` = '$catid'")->fetch();
	$stmt = $db->prepare('SELECT * FROM download__category WHERE id = :catid');
	$stmt->execute(['catid' => $catid]);
	$catinfo = $stmt->fetch();
	$catname = '' . $catinfo['rus_name'] . '';
	$catdir = '' . $catinfo['dir'] . '';

} else {
	$catdir = '/';
	$catname = null;
}

$total = 0;
// Выводим результаты поиска
if ($search && empty($error)) {
    // Подготавливаем данные для запроса
    $search = preg_replace("/[^\w\x7F-\xFF\s]/", ' ', $search);
    $search_db = strtr($search, ['_' => '\\_', '%' => '\\%', '*' => '%']);
    $search_db = '%' . $search_db . '%';
    $search_db = $db->quote($search_db);

    if ($stag) {
        $sql = ($stag ? '`tag`' : '`rus_name`') . ' LIKE ' . $search_db;
    } elseif ($sven) {
        $sql = ($sven ? '`vendor`' : '`rus_name`') . ' LIKE ' . $search_db;
    } else {
        $sql = ($id ? '`about`' : '`rus_name`') . ' LIKE ' . $search_db;
    }

    $total = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2' AND dir LIKE '%".$catdir."%' AND ${sql}")->fetchColumn();
    if ($total) {
        $req_down = $db->query("SELECT * FROM `download__files` WHERE `type` = '2' AND dir LIKE '%".$catdir."%' AND ${sql} ORDER BY `rus_name` LIMIT ${start}, " . $user->config->kmess);
        $files = [];
        while ($res_down = $req_down->fetch()) {
            $files[] = Download::displayFile($res_down);
        }
    }
} elseif ($error) {
    // FAQ по поиску и вывод ошибки
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('Error'),
            'type'          => 'alert-danger',
            'message'       => $error,
            'back_url'      => $url,
            'back_url_name' => __('Downloads'),
        ]
    );
    exit;
}

$pagination_url = http_build_query(
    [
        'act' => 'search',
        'search' => $search,
        'id' => $id,
        'stag' => $stag,
        'sven' => $sven,
    ]
);

echo $view->render(
    'downloads::search',
    [
        'title'           => __('Search results'),
        'page_title'      => __('Search results'),
        'pagination'      => $tools->displayPagination('?' . $pagination_url . '&amp;', $start, $total, $user->config->kmess),
        'files'           => $files ?? [],
        'total'           => $total,
        'search_query'    => htmlspecialchars($search),
        'id'              => $id,
        'stag'            => $stag,
        'sven'            => $sven,
        'urls'            => $urls,
        'catid'           => $catid,
		'catname'         => $catname,
        'show_empty_info' => ! empty($search),
    ]
);
