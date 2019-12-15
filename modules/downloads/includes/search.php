<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Utility\Tools $tools
 */

$id = isset($_REQUEST['id']) ? abs((int) ($_REQUEST['id'])) : 0;

// Поиск файлов
$search_post = isset($_POST['search']) ? trim($_POST['search']) : false;
$search_get = isset($_GET['search']) ? rawurldecode(trim($_GET['search'])) : '';
$search = $search_post ? $search_post : $search_get;

require 'classes/download.php';

// Форма для поиска
echo '<div class="phdr"><a href="?"><b>' . _t('Downloads') . '</b></a> | ' . _t('Search') . '</div>' .
    '<form action="?act=search" method="post"><div class="gmenu"><p>' .
    _t('File Name') . ':<br><input type="text" name="search" value="' . htmlspecialchars($search) . '" /><br>' .
    '<input name="id" type="checkbox" value="1" ' . ($id ? 'checked="checked"' : '') . '/> ' . _t('Search in description') . '<br>' .
    '<input type="submit" value="' . _t('Search') . '" name="submit" /><br>' .
    '</p></div></form>';

// Проверяем на коректность ввода
$error = false;

if (! empty($search) && mb_strlen($search) < 2 || mb_strlen($search) > 64) {
    $error = _t('Invalid file name length. Allowed a minimum of 3 and a maximum of 64 characters.');
}

// Выводим результаты поиска
if ($search && ! $error) {
    // Подготавливаем данные для запроса
    $search = preg_replace("/[^\w\x7F-\xFF\s]/", ' ', $search);
    $search_db = strtr($search, ['_' => '\\_', '%' => '\\%', '*' => '%']);
    $search_db = '%' . $search_db . '%';
    $search_db = $db->quote($search_db);
    $sql = ($id ? '`about`' : '`rus_name`') . ' LIKE ' . $search_db;

    // Результаты поиска
    echo '<div class="phdr"><b>' . _t('Search results') . '</b></div>';
    $total = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2'  AND ${sql}")->fetchColumn();

    if ($total > $user->config->kmess) {
        $check_search = htmlspecialchars(rawurlencode($search));
        echo '<div class="topmenu">' . $tools->displayPagination('?act=search&amp;search=' . $check_search . '&amp;id=' . $id . '&amp;', $start, $total, $user->config->kmess) . '</div>';
    }

    if ($total) {
        $req_down = $db->query("SELECT * FROM `download__files` WHERE `type` = '2'  AND ${sql} ORDER BY `rus_name` ASC LIMIT ${start}, " . $user->config->kmess);
        $i = 0;

        while ($res_down = $req_down->fetch()) {
            echo(($i++ % 2) ? '<div class="list2">' : '<div class="list1">') . Download::displayFile($res_down) . '</div>';
        }
    } else {
        echo '<div class="rmenu"><p>' . _t('No items found') . '</p></div>';
    }

    echo '<div class="phdr">' . _t('Total') . ':  ' . $total . '</div>';

    // Навигация
    if ($total > $user->config->kmess) {
        echo '<div class="topmenu">' . $tools->displayPagination('?act=search&amp;search=' . $check_search . '&amp;id=' . $id . '&amp;', $start, $total, $user->config->kmess) . '</div>' .
            '<p><form action="?" method="get">' .
            '<input type="hidden" value="' . $check_search . '" name="search" />' .
            '<input type="hidden" value="search" name="act" />' .
            '<input type="hidden" value="' . $id . '" name="id" />' .
            '<input type="text" name="page" size="2"/><input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
    }
    echo '<p><a href="?act=search">' . _t('New Search') . '</a></p>';
} else {
    // FAQ по поиску и вывод ошибки
    if ($error) {
        echo $error;
    }

    echo '<div class="phdr"><small>' . _t('Search by file Name and is case insensitive.<br>The length of the request: 2mins. 64макс.') . '</small></div>';
}

echo '<p><a href="?">' . _t('Downloads') . '</a></p>';
echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
