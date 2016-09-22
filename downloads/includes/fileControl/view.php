<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);
$lng = core::load_lng('dl');
$url = $set['homeurl'] . '/downloads/';
$homeurl = $set['homeurl'];

// Выводим файл
$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();

if (!$req_down->rowCount() || !is_file($res_down['dir'] . '/' . $res_down['name'])) {
    echo $lng['not_found_file'] . '<a href="' . $url . '">' . $lng['download_title'] . '</a>';
    exit;
}

$title_pages = htmlspecialchars(mb_substr($res_down['rus_name'], 0, 30));
$textl = mb_strlen($res_down['rus_name']) > 30 ? $title_pages . '...' : $title_pages;

if ($res_down['type'] == 3) {
    echo '<div class="rmenu">' . $lng['file_mod'] . '</div>';
    if ($rights < 6 && $rights != 4) {
        exit;
    }
}


echo '<div class="phdr"><b>' . htmlspecialchars($res_down['rus_name']) . '</b></div>';
$format_file = htmlspecialchars($res_down['name']);

// Управление закладками
if ($user_id) {
    $bookmark = $db->query("SELECT COUNT(*) FROM `download__bookmark` WHERE `file_id` = " . $id . "  AND `user_id` = " . $user_id)->fetchColumn();

    if (isset($_GET['addBookmark']) && !$bookmark) {
        $db->exec("INSERT INTO `download__bookmark` SET `file_id`='" . $id . "', `user_id`=" . $user_id);
        $bookmark = 1;
    } elseif (isset($_GET['delBookmark']) && $bookmark) {
        $db->exec("DELETE FROM `download__bookmark` WHERE `file_id`='" . $id . "' AND `user_id`=" . $user_id);
        $bookmark = 0;
    }

    echo '<div class="topmenu">';

    if (!$bookmark) {
        echo '<a href="' . $url . '?act=view&amp;id=' . $id . '&amp;addBookmark">' . $lng['add_favorite'] . '</a>';
    } else {
        echo '<a href="' . $url . '?act=view&amp;id=' . $id . '&amp;delBookmark">' . $lng['delete_favorite'] . '</a>';
    }

    echo '</div>';
}

// Получаем список скриншотов
$text_info = '';
$screen = [];

if (is_dir($screens_path . '/' . $id)) {
    $dir = opendir($screens_path . '/' . $id);

    while ($file = readdir($dir)) {
        if (($file != '.') && ($file != "..") && ($file != "name.dat") && ($file != ".svn") && ($file != "index.php")) {
            $screen[] = $screens_path . '/' . $id . '/' . $file;
        }
    }

    closedir($dir);
}

// Плейер видео файлов
if (($format_file == 'mp4' || $format_file == 'flv') && !Functions::isMobile()) {
    echo '<div class="menu"><b>' . $lng['view'] . '</b><br />
	<div id="mediaplayer">JW Player goes here</div>
    <script type="text/javascript" src="' . $homeurl . 'files/download/system/players/mediaplayer-5.7-viral/jwplayer.js"></script>
    <script type="text/javascript">
        jwplayer("mediaplayer").setup({
            flashplayer: "' . $homeurl . 'files/download/system/players/mediaplayer-5.7-viral/player.swf",
            file: "' . $homeurl . $res_down['dir'] . '/' . $res_down['name'] . '",
            image: "' . $homeurl . 'assets/misc/thumbinal.php?type=3&amp;img=' . rawurlencode($screen[0]) . '"
        });
    </script></div>';
}

// Получаем данные
if ($format_file == 'jpg' || $format_file == 'jpeg' || $format_file == 'gif' || $format_file == 'png') {
    $info_file = getimagesize($res_down['dir'] . '/' . $res_down['name']);
    //echo '<div class="gmenu"><img src="' . Vars::$HOME_URL . 'assets/misc/thumbinal.php?type=2&amp;img=' . rawurlencode($res_down['dir'] . '/' . $res_down['name']) . '" alt="preview" /></div>';
    $screen[] = $res_down['dir'] . '/' . $res_down['name'];
    $text_info = '<b>' . $lng['resolution'] . ': </b>' . $info_file[0] . 'x' . $info_file[1] . ' px<br />';
} else {
    if (($format_file == '3gp' || $format_file == 'avi' || $format_file == 'mp4') && !$screen && $set_down['video_screen']) {
        $screen[] = Download::screenAuto($res_down['dir'] . '/' . $res_down['name'], $res_down['id'], $format_file);
    } elseif (($format_file == 'thm' || $format_file == 'nth') && !$screen && $set_down['theme_screen']) {
        $screen[] = Download::screenAuto($res_down['dir'] . '/' . $res_down['name'], $res_down['id'], $format_file);
    } elseif ($format_file == 'mp3') {
        if (!Functions::isMobile()) {//TODO: убрать Flash
            $text_info = '<object type="application/x-shockwave-flash" data="' . $homeurl . 'files/download/system/players/player.swf" width="240" height="20" id="dewplayer" name="dewplayer">' .
                '<param name="wmode" value="transparent" /><param name="movie" value="' . $homeurl . 'files/download/system/download/players/player.swf" />' .
                '<param name="flashVars" value="mp3=' . $homeurl . str_replace('../', '', $res_down['dir']) . '/' . $res_down['name'] . '" /> </object><br />';
        }

        require(SYSPATH . 'lib/getid3/getid3.php');
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

        $text_info .= '<b>' . $lng['mp3_channels'] . '</b>: ' . $getid['audio']['channels'] . ' (' . $getid['audio']['channelmode'] . ')<br/>' .
            '<b>' . $lng['mp3_sample_rate'] . '</b>: ' . ceil($getid['audio']['sample_rate'] / 1000) . ' KHz<br/>' .
            '<b>' . $lng['mp3_bitrate'] . '</b>: ' . ceil($getid['audio']['bitrate'] / 1000) . ' Kbit/s<br/>' .
            '<b>' . $lng['mp3_playtime_seconds'] . '</b>: ' . date('i:s', $getid['playtime_seconds']) . '<br />';

        if ($mp3info) {
            if (isset($tagsArray['artist'][0])) {
                $text_info .= '<b>' . $lng['mp3_artist'] . '</b>: ' . Download::mp3tagsOut($tagsArray['artist'][0]) . '<br />';
            }
            if (isset($tagsArray['title'][0])) {
                $text_info .= '<b>' . $lng['mp3_title'] . '</b>: ' . Download::mp3tagsOut($tagsArray['title'][0]) . '<br />';
            }
            if (isset($tagsArray['album'][0])) {
                $text_info .= '<b>' . $lng['mp3_album'] . '</b>: ' . Download::mp3tagsOut($tagsArray['album'][0]) . '<br />';
            }
            if (isset($tagsArray['genre'][0])) {
                $text_info .= '<b>' . $lng['mp3_genre'] . '</b>: ' . Download::mp3tagsOut($tagsArray['genre'][0]) . '<br />';
            }
            if (intval($tagsArray['year'][0])) {
                $text_info .= '<b>' . $lng['mp3_year'] . '</b>: ' . (int)$tagsArray['year'][0] . '<br />';
            }
        }
    }
}

// Выводим скриншоты
if ($screen) {
    $total = count($screen);
    if ($total > 1) {
        if ($page >= $total) {
            $page = $total;
        }

        echo '<div class="topmenu"> ' . Functions::displayPagination($url . '?act=view&amp;id=' . $id . '&amp;', $page - 1, $total, 1) . '</div>' .
            '<div class="gmenu"><b>' . $lng['screen_file'] . ' (' . $page . '/' . $total . '):</b><br />' .
            '<img src="' . $homeurl . 'assets/misc/thumbinal.php?type=2&amp;img=' . rawurlencode($screen[$page - 1]) . '" alt="screen" /></div>';
    } else {
        echo '<div class="gmenu"><b>' . $lng['screen_file'] . ':</b><br />' .
            '<img src="' . $homeurl . 'assets/misc/thumbinal.php?type=2&amp;img=' . rawurlencode($screen[0]) . '" alt="screen" /></div>';
    }
}

// Выводим данные
//Mobi::$USER = $res_down['user_id'];
App::user()->settings['avatars'] = 0;
//TODO: Переделать на класс Users
//$user = Mobi::getUser();
$user = 'Admin';
echo '<div class="list1"><b>' . $lng['name_for_server'] . ':</b> ' . $res_down['name'] . '<br />' .
    '<b>' . $lng['user_upload'] . ':</b> ' . $user . '<br />' . $text_info .
    '<b>' . $lng['number_of_races'] . ':</b> ' . $res_down['field'] . '<br />';

if ($res_down['about']) {
    echo '<b>' . $lng['dir_desc'] . ':</b> ' . htmlspecialchars($res_down['about']);
}

echo '<div class="sub"></div>';

// Рейтинг файла
$file_rate = explode('|', $res_down['rate']);
if ((isset($_GET['plus']) || isset($_GET['minus'])) && !isset($_SESSION['rate_file_' . $id]) && $user_id) {
    if (isset($_GET['plus'])) {
        $file_rate[0] = $file_rate[0] + 1;
    } else {
        $file_rate[1] = $file_rate[1] + 1;
    }

    $db->exec("UPDATE `download__files` SET `rate`='" . $file_rate[0] . '|' . $file_rate[1] . "' WHERE `id`=" . $id);
    echo '<b><span class="green">' . $lng['your_vote'] . '</span></b><br />';
    $_SESSION['rate_file_' . $id] = true;
}

$sum = ($file_rate[1] + $file_rate[0]) ? round(100 / ($file_rate[1] + $file_rate[0]) * $file_rate[0]) : 50;
echo '<b>' . $lng['rating'] . ' </b>';

if (!isset($_SESSION['rate_file_' . $id]) && $user_id) {
    echo '(<a href="' . $url . '?act=view&amp;id=' . $id . '&amp;plus">+</a>/<a href="' . $url . '?act=view&amp;id=' . $id . '&amp;minus">-</a>)';
} else {
    echo '(+/-)';
}

echo ': <b><span class="green">' . $file_rate[0] . '</span>/<span class="red">' . $file_rate[1] . '</span></b><br />' .
    '<img src="' . $homeurl . 'assets/misc/rating.php?img=' . $sum . '" alt="' . $lng['rating'] . '" />';

// Скачка изображения в особом размере
if ($format_file == 'jpg' || $format_file == 'jpeg' || $format_file == 'gif' || $format_file == 'png') {
    $array = ['101x80', '128x128', '128x160', '176x176', '176x208', '176x220', '208x208', '208x320', '240x266', '240x320', '240x432', '352x416', '480x800'];
    echo '<div class="sub"></div>' .
        '<form action="' . $url . '" method="get">' .
        '<input name="id" type="hidden" value="' . $id . '" />' .
        '<input name="act" type="hidden" value="custom_size" />' .
        $lng['custom_size'] . ': ' . '<select name="img_size">';
    $img = 0;

    foreach ($array as $v) {
        echo '<option value="' . $img . '">' . $v . '</option>';
        ++$img;
    }

    echo '</select><br />' .
        $lng['quality'] . ': <select name="val">' .
        '<option value="100">100</option>' .
        '<option value="90">90</option>' .
        '<option value="80">80</option>' .
        '<option value="70">70</option>' .
        '<option value="60">60</option>' .
        '<option value="50">50</option>' .
        '</select><br />' .
        '<input name="proportion" type="checkbox" value="1" />&nbsp;' . $lng['proportion'] . '<br />' .
        '<input type="submit" value="' . $lng['download'] . '" /></form>';
}

//TODO: Переделать на получение настроек из таблицы модулей
if (App::cfg()->sys->acl_downloads_comm || $rights >= 7) {
    echo '<div class="sub"></div><a href="' . $url . '?act=comments&amp;id=' . $res_down['id'] . '">' . $lng['comments'] . '</a> (' . $res_down['total'] . ')';
}

echo '</div>';

// Запрашиваем дополнительные файлы
$req_file_more = $db->query("SELECT * FROM `download__more` WHERE `refid` = " . $id . " ORDER BY `time` ASC");
$total_files_more = $req_file_more->rowCount();

// Скачка файла
echo '<div class="phdr"><b>' . ($total_files_more ? $lng['download_files'] : $lng['download_file']) . '</b></div>' .
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
                'format' => Functions::format($res_file_more['name']),
                'res'    => $res_file_more,
                'more'   => $res_file_more['id'],
            ]) . '</div>';
    }
}

// Навигация
echo '<div class="phdr">' . Download::navigation(['dir' => $res_down['dir'], 'refid' => 1, 'count' => 0]) . '</div>';

// Управление файлами
if ($rights > 6 || $rights == 4) {
    echo '<p><div class="func">' .
        '<a href="' . $url . '?act=edit_file&amp;id=' . $id . '">' . $lng['edit_file'] . '</a><br />' .
        '<a href="' . $url . '?act=edit_about&amp;id=' . $id . '">' . $lng['edit_about'] . '</a><br />' .
        '<a href="' . $url . '?act=edit_screen&amp;id=' . $id . '">' . $lng['edit_screen'] . '</a><br />' .
        '<a href="' . $url . '?act=files_more&amp;id=' . $id . '">' . $lng['files_more'] . '</a><br />' .
        '<a href="' . $url . '?act=delete_file&amp;id=' . $id . '">' . $lng['delete_file'] . '</a>';

    if ($rights > 6) {
        echo '<br /><a href="' . $url . '?act=transfer_file&amp;id=' . $id . '">' . $lng['transfer_file'] . '</a>';
        if ($format_file == 'mp3') {
            echo '<br /><a href="' . $url . '?act=mp3tags&amp;id=' . $id . '">' . $lng['edit_mp3tags'] . '</a>';
        }
    }

    echo '</div></p>';
}
