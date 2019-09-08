<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

require '../system/head.php';
require 'classes/download.php';

// Выводим файл
$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();

if (!$req_down->rowCount() || !is_file($res_down['dir'] . '/' . $res_down['name'])) {
    echo '<div class="rmenu"><p>' . _t('File not found') . '<br><a href="?">' . _t('Downloads') . '</a></p></div>';
    require '../system/end.php';
    exit;
}

$title_pages = htmlspecialchars(mb_substr($res_down['rus_name'], 0, 30));
$textl = mb_strlen($res_down['rus_name']) > 30 ? $title_pages . '...' : $title_pages;

if ($res_down['type'] == 3) {
    echo '<div class="rmenu">' . _t('The file is on moderation') . '</div>';

    if ($systemUser->rights < 6 && $systemUser->rights != 4) {
        require '../system/end.php';
        exit;
    }
}

echo '<div class="phdr">' . Download::navigation(['dir' => $res_down['dir'], 'refid' => 1, 'count' => 0]) . '</div>';
$format = explode('.', $res_down['name']);
$format_file = array_pop($format);

// Получаем список скриншотов
$text_info = '';
$screen = [];

if (is_dir(DOWNLOADS_SCR . $id)) {
    $dir = opendir(DOWNLOADS_SCR . $id);

    while ($file = readdir($dir)) {
        if (($file != '.') && ($file != "..") && ($file != "name.dat") && ($file != ".svn") && ($file != "index.php")) {
            $screen[] = '../files/downloads/screen/' . $id . '/' . $file;
        }
    }

    closedir($dir);
}

switch ($format_file) {
    case 'mp3':
        // Проигрываем аудио файлы
        $text_info = '<audio src="' . $config['homeurl'] . str_replace('../', '/', $res_down['dir']) . '/' . $res_down['name'] . '" controls></audio><br>';
        require 'classes/getid3/getid3.php';
        $getID3 = new getID3;
        $getID3->encoding = 'cp1251';
        $getid = $getID3->analyze($res_down['dir'] . '/' . $res_down['name']);
        $mp3info = true;

        if (!empty($getid['tags']['id3v2'])) {
            $tagsArray = $getid['tags']['id3v2'];
        } elseif (!empty($getid['tags']['id3v1'])) {
            $tagsArray = $getid['tags']['id3v1'];
        } else {
            $mp3info = false;
        }

        $text_info .= '<b>' . _t('Channels') . '</b>: ' . $getid['audio']['channels'] . ' (' . $getid['audio']['channelmode'] . ')<br>' .
            '<b>' . _t('Sample rate') . '</b>: ' . ceil($getid['audio']['sample_rate'] / 1000) . ' KHz<br>' .
            '<b>' . _t('Bitrate') . '</b>: ' . ceil($getid['audio']['bitrate'] / 1000) . ' Kbit/s<br>' .
            '<b>' . _t('Duration') . '</b>: ' . date('i:s', $getid['playtime_seconds']) . '<br>';

        if ($mp3info) {
            if (isset($tagsArray['artist'][0])) {
                $text_info .= '<b>' . _t('Artist') . '</b>: ' . Download::mp3tagsOut($tagsArray['artist'][0]) . '<br>';
            }
            if (isset($tagsArray['title'][0])) {
                $text_info .= '<b>' . _t('Title') . '</b>: ' . Download::mp3tagsOut($tagsArray['title'][0]) . '<br>';
            }
            if (isset($tagsArray['album'][0])) {
                $text_info .= '<b>' . _t('Album') . '</b>: ' . Download::mp3tagsOut($tagsArray['album'][0]) . '<br>';
            }
            if (isset($tagsArray['genre'][0])) {
                $text_info .= '<b>' . _t('Genre') . '</b>: ' . Download::mp3tagsOut($tagsArray['genre'][0]) . '<br>';
            }
            if (intval($tagsArray['year'][0])) {
                $text_info .= '<b>' . _t('Year') . '</b>: ' . (int)$tagsArray['year'][0] . '<br>';
            }
        }
        break;

    case 'avi':
    case 'webm':
    case 'mp4':
        // Проигрываем видео файлы
        echo '<div class="gmenu"><video src="' . $config['homeurl'] . str_replace('../', '/', $res_down['dir']) . '/' . $res_down['name'] . '" controls></video></div>';
        break;

    case 'jpg':
    case 'jpeg':
    case 'gif':
    case 'png':
        $info_file = getimagesize($res_down['dir'] . '/' . $res_down['name']);
        echo '<div class="gmenu"><img src="preview.php?type=2&amp;img=' . rawurlencode($res_down['dir'] . '/' . $res_down['name']) . '" alt="preview" /></div>';
        $text_info = '<span class="gray">' . _t('Resolution') . ': </span>' . $info_file[0] . 'x' . $info_file[1] . ' px<br>';
        break;
}

// Выводим скриншоты
if (!empty($screen)) {
    $total = count($screen);

    if ($total > 1) {
        if ($page >= $total) {
            $page = $total;
        }

        echo '<div class="gmenu"><b>' . _t('Screenshot') . ' (' . $page . '/' . $total . '):</b><br>' .
            '<img src="preview.php?type=2&amp;img=' . rawurlencode($screen[$page - 1]) . '" alt="screen" /></div>';
        echo '<div class="topmenu"> ' . $tools->displayPagination('?act=view&amp;id=' . $id . '&amp;', $page - 1, $total, 1) . '</div>';
    } else {
        echo '<div class="gmenu"><b>' . _t('Screenshot') . ':</b><br>' .
            '<img src="preview.php?type=2&amp;img=' . rawurlencode($screen[0]) . '" alt="screen" /></div>';
    }
}

// Выводим данные
$user = $db->query("SELECT `name`, `id` FROM `users` WHERE `id` = " . $res_down['user_id'])->fetch();
echo '<div class="list1">'
    . '<p><h3>' . $res_down['rus_name'] . '</h3></p>'
    . '<span class="gray">' . _t('File name') . ':</span> ' . $res_down['name'] . '<br>'
    . '<span class="gray">' . _t('Uploaded by') . ':</span> ' . $user['name'] . '<br>' . $text_info
    . '<span class="gray">' . _t('Downloads') . ':</span> ' . $res_down['field'];

echo '</div>';

if (!empty($res_down['about'])) {
    echo '<div class="topmenu" style="font-size: small">' . $tools->checkout($res_down['about'],1 ,1) . '</div>';
}

echo '<div class="list1"><p>';

// Рейтинг файла
$file_rate = explode('|', $res_down['rate']);
if ((isset($_GET['plus']) || isset($_GET['minus'])) && !isset($_SESSION['rate_file_' . $id]) && $systemUser->isValid()) {
    if (isset($_GET['plus'])) {
        $file_rate[0] = $file_rate[0] + 1;
    } else {
        $file_rate[1] = $file_rate[1] + 1;
    }

    $db->exec("UPDATE `download__files` SET `rate`='" . $file_rate[0] . '|' . $file_rate[1] . "' WHERE `id`=" . $id);
    echo '<b><span class="green">' . _t('Voice adopted') . '</span></b><br>';
    $_SESSION['rate_file_' . $id] = true;
}

$sum = ($file_rate[1] + $file_rate[0]) ? round(100 / ($file_rate[1] + $file_rate[0]) * $file_rate[0]) : 50;
echo '<b>' . _t('Rating') . ' </b>';

if (!isset($_SESSION['rate_file_' . $id]) && $systemUser->isValid()) {
    echo '(<a href="?act=view&amp;id=' . $id . '&amp;plus">+</a>/<a href="?act=view&amp;id=' . $id . '&amp;minus">-</a>)';
} else {
    echo '(+/-)';
}

echo ': <b><span class="green">' . $file_rate[0] . '</span>/<span class="red">' . $file_rate[1] . '</span></b><br>' .
    '<img src="rating.php?img=' . $sum . '" alt="' . _t('Rating') . '" /></p>';

if ($config['mod_down_comm'] || $systemUser->rights >= 7) {
    echo '<p><a href="?act=comments&amp;id=' . $res_down['id'] . '">' . _t('Comments') . '</a> (' . $res_down['comm_count'] . ')</p>';
}

echo '</div>';

// Запрашиваем дополнительные файлы
$req_file_more = $db->query("SELECT * FROM `download__more` WHERE `refid` = " . $id . " ORDER BY `time` ASC");
$total_files_more = $req_file_more->rowCount();

// Скачка файла
echo '<div class="phdr"><b>' . ($total_files_more ? _t('Files for Download') : _t('Files for Download')) . '</b></div>' .
    '<div class="list1">' . Download::downloadLlink([
        'format' => $format_file,
        'res'    => $res_down,
    ]) . '</div>';

// Дополнительные файлы
if ($total_files_more) {
    $i = 0;
    while ($res_file_more = $req_file_more->fetch()) {
        $res_file_more['dir'] = $res_down['dir'];
        $res_file_more['text'] = $res_file_more['rus_name'];
        echo (($i++ % 2) ? '<div class="list1">' : '<div class="list2">') .
            Download::downloadLlink([
                'format' => pathinfo($res_file_more['name'], PATHINFO_EXTENSION),
                'res'    => $res_file_more,
                'more'   => $res_file_more['id'],
            ]) . '</div>';
    }
}

// Управление закладками
if ($systemUser->isValid()) {
    $bookmark = $db->query("SELECT COUNT(*) FROM `download__bookmark` WHERE `file_id` = " . $id . "  AND `user_id` = " . $systemUser->id)->fetchColumn();

    if (isset($_GET['addBookmark']) && !$bookmark) {
        $db->exec("INSERT INTO `download__bookmark` SET `file_id`='" . $id . "', `user_id` = " . $systemUser->id);
        $bookmark = 1;
    } elseif (isset($_GET['delBookmark']) && $bookmark) {
        $db->exec("DELETE FROM `download__bookmark` WHERE `file_id`='" . $id . "' AND `user_id` = " . $systemUser->id);
        $bookmark = 0;
    }

    echo '<div class="phdr">';

    if (!$bookmark) {
        echo '<a href="?act=view&amp;id=' . $id . '&amp;addBookmark">' . _t('Add to Favorites') . '</a>';
    } else {
        echo '<a href="?act=view&amp;id=' . $id . '&amp;delBookmark">' . _t('Remove from Favorites') . '</a>';
    }

    echo '</div>';
}

// Управление файлами
if ($systemUser->rights > 6 || $systemUser->rights == 4) {
    echo '<p><div class="func">' .
        '<a href="?act=edit_file&amp;id=' . $id . '">' . _t('Edit File') . '</a><br>' .
        '<a href="?act=edit_about&amp;id=' . $id . '">' . _t('Edit Description') . '</a><br>' .
        '<a href="?act=edit_screen&amp;id=' . $id . '">' . _t('Managing Screenshots') . '</a><br>' .
        '<a href="?act=files_more&amp;id=' . $id . '">' . _t('Additional Files') . '</a><br>' .
        '<a href="?act=delete_file&amp;id=' . $id . '">' . _t('Delete File') . '</a>';

    if ($systemUser->rights > 6) {
        echo '<br><a href="?act=transfer_file&amp;id=' . $id . '">' . _t('Move File') . '</a>';
        if ($format_file == 'mp3') {
            echo '<br><a href="?act=mp3tags&amp;id=' . $id . '">' . _t('Edit MP3 Tags') . '</a>';
        }
    }

    echo '</div></p>';
}

require '../system/end.php';
