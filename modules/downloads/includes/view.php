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
use Downloads\Screen;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 */

// Выводим файл
$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();

if (! $req_down->rowCount() || ! is_file($res_down['dir'] . '/' . $res_down['name'])) {
    http_response_code(404);
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('File not found'),
            'type'          => 'alert-danger',
            'message'       => __('File not found'),
            'back_url'      => $url,
            'back_url_name' => __('Downloads'),
        ]
    );
    exit;
}

$title_page = htmlspecialchars($res_down['rus_name']);

if ($res_down['type'] === 3 && $user->rights < 6 && $user->rights !== 4) {
    http_response_code(403);
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('The file is on moderation'),
            'type'          => 'alert-danger',
            'message'       => __('The file is on moderation'),
            'back_url'      => $url,
            'back_url_name' => __('Downloads'),
        ]
    );
    exit;
}

Download::navigation(['dir' => $res_down['dir'], 'refid' => 1, 'count' => 0]);

$nav_chain->add($res_down['rus_name']);

$extension = strtolower(pathinfo($res_down['name'], PATHINFO_EXTENSION));

$urls = [
    'downloads' => $url,
    'back'      => '?id=' . $res_down['refid'],
];

$file_data = $res_down;

// Получаем список скриншотов
$text_info = '';
$screen = Screen::getScreens($id);
$file_data['file_type'] = 'other';
$file_data['screenshots'] = $screen;
$file_properties = [];
switch ($extension) {
    case 'mp3':
    case 'aac':
    case 'm4a':
        $getID3 = new getID3();
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

        $file_properties = [
            [
                'name'  => __('Channels'),
                'value' => $getid['audio']['channels'] . ' (' . $getid['audio']['channelmode'] . ')',
            ],
            [
                'name'  => __('Sample rate'),
                'value' => ceil($getid['audio']['sample_rate'] / 1000) . ' KHz',
            ],
            [
                'name'  => __('Bitrate'),
                'value' => ceil($getid['audio']['bitrate'] / 1000) . ' Kbit/s',
            ],
            [
                'name'  => __('Duration'),
                'value' => $getid['playtime_string'],
            ],
        ];

        if ($mp3info) {
            if (isset($tagsArray['artist'][0])) {
                $file_properties[] = [
                    'name'  => __('Artist'),
                    'value' => Download::mp3tagsOut($tagsArray['artist'][0]),
                ];
            }
            if (isset($tagsArray['title'][0])) {
                $file_properties[] = [
                    'name'  => __('Title'),
                    'value' => Download::mp3tagsOut($tagsArray['title'][0]),
                ];
            }
            if (isset($tagsArray['album'][0])) {
                $file_properties[] = [
                    'name'  => __('Album'),
                    'value' => Download::mp3tagsOut($tagsArray['album'][0]),
                ];
            }
            if (isset($tagsArray['genre'][0])) {
                $file_properties[] = [
                    'name'  => __('Genre'),
                    'value' => Download::mp3tagsOut($tagsArray['genre'][0]),
                ];
            }
            if (isset($tagsArray['year'][0])) {
                $file_properties[] = [
                    'name'  => __('Year'),
                    'value' => Download::mp3tagsOut($tagsArray['year'][0]),
                ];
            }
        }

        $file_data['file_type'] = 'audio';
        break;

    case 'avi':
    case 'webm':
    case 'mov':
    case 'mp4':
        $getID3 = new getID3();
        $getID3->encoding = 'cp1251';
        $getid = $getID3->analyze($res_down['dir'] . '/' . $res_down['name']);
        if (! empty($getid['video'])) {
            if (isset($getid['video']['fourcc_lookup'])) {
                $file_properties[] = [
                    'name'  => __('Codec'),
                    'value' => $getid['video']['fourcc_lookup'],
                ];
            }
            if (isset($getid['video']['frame_rate'])) {
                $file_properties[] = [
                    'name'  => __('Frame rate'),
                    'value' => $getid['video']['frame_rate'] . ' FPS',
                ];
            }
            if (isset($getid['video']['bitrate'])) {
                $file_properties[] = [
                    'name'  => __('Bitrate'),
                    'value' => ceil($getid['video']['bitrate'] / 1000) . ' Kbit/s',
                ];
            }
            if (isset($getid['playtime_string'])) {
                $file_properties[] = [
                    'name'  => __('Duration'),
                    'value' => $getid['playtime_string'],
                ];
            }
            if (isset($getid['video']['resolution_x'])) {
                $file_properties[] = [
                    'name'  => __('Resolution'),
                    'value' => $getid['video']['resolution_x'] . 'x' . $getid['video']['resolution_y'] . 'px',
                ];
            }
        }

        $file_data['file_type'] = 'video';
        break;

    case 'jpg':
    case 'jpeg':
    case 'gif':
    case 'png':
        $file_path = $res_down['dir'] . '/' . $res_down['name'];
        $screen[] = [
            'url'     => '/' . $file_path,
            'preview' => '/assets/modules/downloads/preview.php?type=2&amp;img=' . rawurlencode($file_path),
        ];
        $file_data['screenshots'] = $screen;
        $info_file = getimagesize($res_down['dir'] . '/' . $res_down['name']);
        $file_data['image_info'] = [
            'width'  => $info_file[0],
            'height' => $info_file[1],
        ];
        $file_data['file_type'] = 'image';
        break;
}

$file_data['file_properties'] = $file_properties;

$file_data['description'] = $tools->checkout($res_down['about'], 1, 1);

// Выводим данные
$foundUser = $db->query('SELECT `name`, `id` FROM `users` WHERE `id` = ' . $res_down['user_id'])->fetch();
$file_data['upload_user'] = $foundUser;

// Рейтинг файла
$file_rate = explode('|', $res_down['rate']);
$file_rate[0] = ! empty($file_rate[0]) ? (int) $file_rate[0] : 0;
$file_rate[1] = ! empty($file_rate[1]) ? (int) $file_rate[1] : 0;

$session_index = 'rate_file_' . $id;
if ((isset($_GET['plus']) || isset($_GET['minus'])) && ! isset($_SESSION[$session_index]) && $user->isValid()) {
    if (isset($_GET['plus'])) {
        ++$file_rate[0];
    } else {
        ++$file_rate[1];
    }

    $db->exec("UPDATE `download__files` SET `rate`='" . $file_rate[0] . '|' . $file_rate[1] . "' WHERE `id`=" . $id);
    $file_data['vote_accepted'] = true;
    $_SESSION['rate_file_' . $id] = true;
}

$file_data['can_vote'] = false;
if (! isset($_SESSION[$session_index]) && $user->isValid()) {
    $file_data['can_vote'] = true;
}

$sum = ($file_rate[1] + $file_rate[0]) ? round(100 / ($file_rate[1] + $file_rate[0]) * $file_rate[0]) : 50;

$file_data['rate'] = $file_rate;

// Запрашиваем дополнительные файлы
$req_file_more = $db->query('SELECT * FROM `download__more` WHERE `refid` = ' . $id . ' ORDER BY `time`');
$total_files_more = $req_file_more->rowCount();

$file_data['main_file'] = Download::downloadLlink(
    [
        'format' => $extension,
        'res'    => $res_down,
    ]
);

$file_data['additional_files'] = [];

// Дополнительные файлы
if ($total_files_more) {
    $i = 0;
    while ($res_file_more = $req_file_more->fetch()) {
        $res_file_more['dir'] = $res_down['dir'];
        $res_file_more['text'] = $res_file_more['rus_name'];

        $file_data['additional_files'][] = Download::downloadLlink(
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
        'file'         => $file_data,
        'in_bookmarks' => $bookmark ?? 0,
        'urls'         => $urls ?? [],
    ]
);
