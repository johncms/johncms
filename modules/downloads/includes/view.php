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

/**
 * @var Johncms\System\Config\Config $config
 * @var PDO $db
 * @var Johncms\Api\ToolsInterface $tools
 * @var Johncms\Api\UserInterface $user
 */

require __DIR__ . '/../classes/download.php';

// Выводим файл
$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();

if (! $req_down->rowCount() || ! is_file($res_down['dir'] . '/' . $res_down['name'])) {
    http_response_code(404);
    echo $view->render(
        'system::pages/result',
        [
            'title'         => _t('File not found'),
            'type'          => 'alert-danger',
            'message'       => _t('File not found'),
            'back_url'      => $url,
            'back_url_name' => _t('Downloads'),
        ]
    );
    exit;
}

$title_page = htmlspecialchars($res_down['rus_name']);

if ($res_down['type'] == 3 && $user->rights < 6 && $user->rights != 4) {
    http_response_code(403);
    echo $view->render(
        'system::pages/result',
        [
            'title'         => _t('The file is on moderation'),
            'type'          => 'alert-danger',
            'message'       => _t('The file is on moderation'),
            'back_url'      => $url,
            'back_url_name' => _t('Downloads'),
        ]
    );
    exit;
}

Download::navigation(['dir' => $res_down['dir'], 'refid' => 1, 'count' => 0]);
$extension = pathinfo($res_down['name'], PATHINFO_EXTENSION);

$urls = [
    'downloads' => $url,
];

$file_array = $res_down;

// Получаем список скриншотов
$text_info = '';
$screen = [];

if (is_dir(DOWNLOADS_SCR . $id)) {
    $dir = opendir(DOWNLOADS_SCR . $id);

    while ($file = readdir($dir)) {
        if (($file != '.') && ($file != '..') && ($file != 'name.dat') && ($file != '.svn') && ($file != 'index.php')) {
            $file_path = UPLOAD_PUBLIC_PATH . 'downloads/screen/' . $id . '/' . $file;
            $screen[] = [
                'file'    => $file_path,
                'preview' => '../assets/modules/downloads/preview.php?type=2&amp;img=' . rawurlencode($file_path),
            ];
        }
    }
    closedir($dir);
}

$file_array['screenshots'] = $screen;

switch ($extension) {
    case 'mp3':
        // Проигрываем аудио файлы
        $text_info = '<audio src="' . $config->homeurl . str_replace(
                '../',
                '/',
                $res_down['dir']
            ) . '/' . $res_down['name'] . '" controls></audio><br>';
        require 'classes/getid3/getid3.php'; //TODO: Разобраться с устаревшим классом
        $getID3 = new getID3;
        $getID3->encoding = 'cp1251';
        $getid = $getID3->analyze($res_down['dir'] . '/' . $res_down['name']);
        $mp3info = true;

        if (! empty($getid['tags']['id3v2'])) {
            $tagsArray = $getid['tags']['id3v2'];
        } elseif (! empty($getid['tags']['id3v1'])) {
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
            if ((int) ($tagsArray['year'][0])) {
                $text_info .= '<b>' . _t('Year') . '</b>: ' . (int) $tagsArray['year'][0] . '<br>';
            }
        }
        break;

    case 'avi':
    case 'webm':
    case 'mp4':
        // Проигрываем видео файлы
        echo '<div class="gmenu"><video src="' . $config->homeurl . str_replace(
                '../',
                '/',
                $res_down['dir']
            ) . '/' . $res_down['name'] . '" controls></video></div>';
        break;

    case 'jpg':
    case 'jpeg':
    case 'gif':
    case 'png':
        $info_file = getimagesize($res_down['dir'] . '/' . $res_down['name']);
        echo '<div class="gmenu"><img src="../assets/modules/downloads/preview.php?type=2&amp;img=' . rawurlencode($res_down['dir'] . '/' . $res_down['name']) . '" alt="preview" /></div>';
        $text_info = '<span class="gray">' . _t('Resolution') . ': </span>' . $info_file[0] . 'x' . $info_file[1] . ' px<br>';
        break;
}

$file_array['description'] = $tools->checkout($res_down['about'], 1, 1);

// Выводим данные
$foundUser = $db->query('SELECT `name`, `id` FROM `users` WHERE `id` = ' . $res_down['user_id'])->fetch();
$file_array['upload_user'] = $foundUser;

// Рейтинг файла
$file_rate = explode('|', $res_down['rate']);
if ((isset($_GET['plus']) || isset($_GET['minus'])) && ! isset($_SESSION['rate_file_' . $id]) && $user->isValid()) {
    if (isset($_GET['plus'])) {
        $file_rate[0] = $file_rate[0] + 1;
    } else {
        $file_rate[1] = $file_rate[1] + 1;
    }

    $db->exec("UPDATE `download__files` SET `rate`='" . $file_rate[0] . '|' . $file_rate[1] . "' WHERE `id`=" . $id);
    $file_array['vote_accepted'] = true;
    $_SESSION['rate_file_' . $id] = true;
}

$sum = ($file_rate[1] + $file_rate[0]) ? round(100 / ($file_rate[1] + $file_rate[0]) * $file_rate[0]) : 50;

$file_array['rate'] = $file_rate;

// Запрашиваем дополнительные файлы
$req_file_more = $db->query('SELECT * FROM `download__more` WHERE `refid` = ' . $id . ' ORDER BY `time` ASC');
$total_files_more = $req_file_more->rowCount();

$file_array['main_file'] = Download::downloadLlink(
    [
        'format' => $extension,
        'res'    => $res_down,
    ]
);

$file_array['additional_files'] = [];

// Дополнительные файлы
if ($total_files_more) {
    $i = 0;
    while ($res_file_more = $req_file_more->fetch()) {
        $res_file_more['dir'] = $res_down['dir'];
        $res_file_more['text'] = $res_file_more['rus_name'];

        $file_array['additional_files'][] = Download::downloadLlink(
            [
                'format' => pathinfo($res_file_more['name'], PATHINFO_EXTENSION),
                'res'    => $res_file_more,
                'more'   => $res_file_more['id'],
            ]
        );
    }
}

// Управление закладками
if ($user->isValid()) {
    $bookmark = $db->query('SELECT COUNT(*) FROM `download__bookmark` WHERE `file_id` = ' . $id . '  AND `user_id` = ' . $user->id)->fetchColumn();

    if (isset($_GET['addBookmark']) && ! $bookmark) {
        $db->exec("INSERT INTO `download__bookmark` SET `file_id`='" . $id . "', `user_id` = " . $user->id);
        $bookmark = 1;
    } elseif (isset($_GET['delBookmark']) && $bookmark) {
        $db->exec("DELETE FROM `download__bookmark` WHERE `file_id`='" . $id . "' AND `user_id` = " . $user->id);
        $bookmark = 0;
    }
}

echo $view->render(
    'downloads::view',
    [
        'title'        => $title_page,
        'page_title'   => $title_page,
        'id'           => $id,
        'file'         => $file_array,
        'in_bookmarks' => $bookmark ?? 0,
        'urls'         => $urls ?? [],
    ]
);
