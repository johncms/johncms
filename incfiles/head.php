<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);

$headmod = isset($headmod) ? $headmod : '';
$textl = isset($textl) ? $textl : $set['copyright'];

echo '<!DOCTYPE html>' .
    "\n" . '<html lang="' . core::$lng_iso . '">' .
    "\n" . '<head>' .
    "\n" . '<meta charset="utf-8">' .
    "\n" . '<meta http-equiv="X-UA-Compatible" content="IE=edge">' .
    "\n" . '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=yes">' .
    "\n" . '<meta name="HandheldFriendly" content="true">' .
    "\n" . '<meta name="MobileOptimized" content="width">' .
    "\n" . '<meta content="yes" name="apple-mobile-web-app-capable">' .
    "\n" . '<meta name="Generator" content="JohnCMS, http://johncms.com">' .
    (!empty($set['meta_key']) ? "\n" . '<meta name="keywords" content="' . $set['meta_key'] . '">' : '') .
    (!empty($set['meta_desc']) ? "\n" . '<meta name="description" content="' . $set['meta_desc'] . '">' : '') .
    "\n" . '<link rel="stylesheet" href="' . $set['homeurl'] . '/theme/' . $set_user['skin'] . '/style.css">' .
    "\n" . '<link rel="shortcut icon" href="' . $set['homeurl'] . '/favicon.ico">' .
    "\n" . '<link rel="alternate" type="application/rss+xml" title="RSS | ' . _t('Site News', 'system') . '" href="' . $set['homeurl'] . '/rss/rss.php">' .
    "\n" . '<title>' . $textl . '</title>' .
    "\n" . '</head><body>';

// Рекламный модуль
$cms_ads = [];

if (!isset($_GET['err']) && $act != '404' && $headmod != 'admin') {
    $view = $user_id ? 2 : 1;
    $layout = ($headmod == 'mainpage' && !$act) ? 1 : 2;
    $req = $db->query("SELECT * FROM `cms_ads` WHERE `to` = '0' AND (`layout` = '$layout' or `layout` = '0') AND (`view` = '$view' or `view` = '0') ORDER BY  `mesto` ASC");

    if ($req->rowCount()) {
        while ($res = $req->fetch()) {
            $name = explode("|", $res['name']);
            $name = htmlentities($name[mt_rand(0, (count($name) - 1))], ENT_QUOTES, 'UTF-8');

            if (!empty($res['color'])) {
                $name = '<span style="color:#' . $res['color'] . '">' . $name . '</span>';
            }

            // Если было задано начертание шрифта, то применяем
            $font = $res['bold'] ? 'font-weight: bold;' : false;
            $font .= $res['italic'] ? ' font-style:italic;' : false;
            $font .= $res['underline'] ? ' text-decoration:underline;' : false;

            if ($font) {
                $name = '<span style="' . $font . '">' . $name . '</span>';
            }

            @$cms_ads[$res['type']] .= '<a href="' . ($res['show'] ? functions::checkout($res['link']) : $set['homeurl'] . '/go.php?id=' . $res['id']) . '">' . $name . '</a><br>';

            if (($res['day'] != 0 && time() >= ($res['time'] + $res['day'] * 3600 * 24))
                || ($res['count_link'] != 0 && $res['count'] >= $res['count_link'])
            ) {
                $db->exec('UPDATE `cms_ads` SET `to` = 1  WHERE `id` = ' . $res['id']);
            }
        }
    }
}

// Рекламный блок сайта
if (isset($cms_ads[0])) {
    echo $cms_ads[0];
}

// Выводим логотип и переключатель языков
echo '<table style="width: 100%;" class="logo"><tr>' .
    '<td valign="bottom"><a href="' . $set['homeurl'] . '">' . functions::image('logo.gif', ['class' => '']) . '</a></td>' .
    ($headmod == 'mainpage' && count(core::$lng_list) > 1 ? '<td align="right"><a href="' . $set['homeurl'] . '/go.php?lng"><b>' . strtoupper(core::$lng_iso) . '</b></a>&#160;<img src="' . $set['homeurl'] . '/images/flags/' . core::$lng_iso . '.gif" alt=""/>&#160;</td>' : '') .
    '</tr></table>';

// Выводим верхний блок с приветствием
echo '<div class="header"> ' . _t('Hi', 'system') . ', ' . ($user_id ? '<b>' . $login . '</b>!' : _t('Guest', 'system') . '!') . '</div>';

// Главное меню пользователя
echo '<div class="tmn">' .
    (isset($_GET['err']) || $headmod != "mainpage" || ($headmod == 'mainpage' && $act) ? '<a href=\'' . $set['homeurl'] . '\'>' . functions::image('menu_home.png') . _t('Home', 'system') . '</a><br>' : '') .
    ($user_id && $headmod != 'office' ? '<a href="' . $set['homeurl'] . '/profile/?act=office">' . functions::image('menu_cabinet.png') . _t('Personal', 'system') . '</a><br>' : '') .
    (!$user_id && $headmod != 'login' ? functions::image('menu_login.png') . '<a href="' . $set['homeurl'] . '/login.php">' . _t('Login', 'system') . '</a>' : '') .
    '</div><div class="maintxt">';

// Рекламный блок сайта
if (!empty($cms_ads[1])) {
    echo '<div class="gmenu">' . $cms_ads[1] . '</div>';
}

// Фиксация местоположений посетителей
$sql = '';
$set_karma = unserialize($set['karma']);

if ($user_id) {
    // Фиксируем местоположение авторизованных
    if (!$datauser['karma_off'] && $set_karma['on'] && $datauser['karma_time'] <= (time() - 86400)) {
        $sql .= " `karma_time` = " . time() . ", ";
    }

    $movings = $datauser['movings'];

    if ($datauser['lastdate'] < (time() - 300)) {
        $movings = 0;
        $sql .= " `sestime` = " . time() . ", ";
    }

    if ($datauser['place'] != $headmod) {
        ++$movings;
        $sql .= " `place` = " . $db->quote($headmod) . ", ";
    }

    if ($datauser['browser'] != $agn) {
        $sql .= " `browser` = " . $db->quote($agn) . ", ";
    }

    $totalonsite = $datauser['total_on_site'];

    if ($datauser['lastdate'] > (time() - 300)) {
        $totalonsite = $totalonsite + time() - $datauser['lastdate'];
    }

    $db->query("UPDATE `users` SET $sql
        `movings` = '$movings',
        `total_on_site` = '$totalonsite',
        `lastdate` = '" . time() . "'
        WHERE `id` = '$user_id'
    ");
} else {
    // Фиксируем местоположение гостей
    $movings = 0;
    $session = md5(core::$ip . core::$ip_via_proxy . core::$user_agent);
    $req = $db->query("SELECT * FROM `cms_sessions` WHERE `session_id` = " . $db->quote($session) . " LIMIT 1");

    if ($req->rowCount()) {
        // Если есть в базе, то обновляем данные
        $res = $req->fetch();
        $movings = ++$res['movings'];

        if ($res['sestime'] < (time() - 300)) {
            $movings = 1;
            $sql .= " `sestime` = '" . time() . "', ";
        }

        if ($res['place'] != $headmod) {
            $sql .= " `place` = " . $db->quote($headmod) . ", ";
        }

        $db->exec("UPDATE `cms_sessions` SET $sql
            `movings` = '$movings',
            `lastdate` = '" . time() . "'
            WHERE `session_id` = " . $db->quote($session) . "
        ");
    } else {
        // Если еще небыло в базе, то добавляем запись
        $db->exec("INSERT INTO `cms_sessions` SET
            `session_id` = '" . $session . "',
            `ip` = '" . core::$ip . "',
            `ip_via_proxy` = '" . core::$ip_via_proxy . "',
            `browser` = " . $db->quote($agn) . ",
            `lastdate` = '" . time() . "',
            `sestime` = '" . time() . "',
            `place` = " . $db->quote($headmod) . "
        ");
    }
}

// Выводим сообщение о Бане
if (!empty($ban)) {
    echo '<div class="alarm">' . _t('Ban', 'system') . '&#160;<a href="' . $set['homeurl'] . '/profile/?act=ban">' . _t('Details', 'system') . '</a></div>';
}

// Ссылки на непрочитанное
if ($user_id) {
    $list = [];
    $new_sys_mail = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE `from_id`='$user_id' AND `read`='0' AND `sys`='1' AND `delete`!='$user_id'")->fetchColumn();

    if ($new_sys_mail) {
        $list[] = '<a href="' . $home . '/mail/index.php?act=systems">' . _t('System', 'system') . '</a> (+' . $new_sys_mail . ')';
    }

    $new_mail = $db->query("SELECT COUNT(*) FROM `cms_mail`
                            LEFT JOIN `cms_contact` ON `cms_mail`.`user_id`=`cms_contact`.`from_id` AND `cms_contact`.`user_id`='$user_id'
                            WHERE `cms_mail`.`from_id`='$user_id'
                            AND `cms_mail`.`sys`='0'
                            AND `cms_mail`.`read`='0'
                            AND `cms_mail`.`delete`!='$user_id'
                            AND `cms_contact`.`ban`!='1'
                            AND `cms_mail`.`spam`='0'")->fetchColumn();

    if ($new_mail) {
        $list[] = '<a href="' . $home . '/mail/index.php?act=new">' . _t('Mail', 'system') . '</a> (+' . $new_mail . ')';
    }

    if ($datauser['comm_count'] > $datauser['comm_old']) {
        $list[] = '<a href="' . core::$system_set['homeurl'] . '/profile/?act=guestbook&amp;user=' . $user_id . '">' . _t('Guestbook', 'system') . '</a> (' . ($datauser['comm_count'] - $datauser['comm_old']) . ')';
    }

    $new_album_comm = $db->query('SELECT COUNT(*) FROM `cms_album_files` WHERE `user_id` = ' . core::$user_id . ' AND `unread_comments` = 1')->fetchColumn();

    if ($new_album_comm) {
        $list[] = '<a href="' . core::$system_set['homeurl'] . '/album/index.php?act=top&amp;mod=my_new_comm">' . _t('Comments', 'system') . '</a>';
    }

    if (!empty($list)) {
        echo '<div class="rmenu">' . _t('Unread', 'system') . ': ' . functions::display_menu($list, ', ') . '</div>';
    }
}
