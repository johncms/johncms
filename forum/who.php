<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                             Content Management System              //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@gazenwagen.com                  //
// Олег Касьянов aka AlkatraZ          alkatraz@gazenwagen.com                //
//                                                                            //
// Информацию о версиях смотрите в прилагаемом файле version.txt              //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');

$textl = 'Кто в форуме?';
$headmod = $id ? 'forum,' . $id : 'forumwho';
require_once('../incfiles/head.php');
$onltime = $realtime - 300;
if (!$user_id) {
    header('Location: index.php');
    exit;
}

// Ссылка на Новые темы
forum_new(1);

$do = isset($_GET['do']) ? $_GET['do'] : '';
if ($id) {
    ////////////////////////////////////////////////////////////
    // Показываем общий список тех, кто в вбранной теме       //
    ////////////////////////////////////////////////////////////
    $req = mysql_query("SELECT `text` FROM `forum` WHERE `id` = '$id' AND `type` = 't' LIMIT 1");
    if (mysql_num_rows($req)) {
        $res = mysql_fetch_assoc($req);
        echo '<div class="phdr"><b>Кто в теме</b> &quot;' . $res['text'] . '&quot;</div>';
        echo '<div class="bmenu">Список ' . ($do == 'guest' ? 'гостей' : 'авторизованных') . '</div>';
        $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `" . ($do == 'guest' ? 'cms_guests' : 'users') . "` WHERE `lastdate` > $onltime AND `place` = 'forum,$id'"), 0);
        if ($total) {
            $req =
                mysql_query("SELECT * FROM `" . ($do == 'guest' ? 'cms_guests' : 'users') . "` WHERE `lastdate` > $onltime AND `place` = 'forum,$id' ORDER BY " . ($do == 'guest' ? "`movings` DESC" : "`name` ASC") . " LIMIT $start, $kmess");
            while ($res = mysql_fetch_assoc($req)) {
                echo ($i % 2) ? '<div class="list2">' : '<div class="list1">';
                $set_user['avatar'] = 0;
                echo show_user($res, 0, ($act == 'guest' || ($rights >= 1 && $rights >= $res['rights']) ? 1 : 0));
                echo '</div>';
                ++$i;
            }
        } else {
            echo '<div class="menu"><p>Никого нет</p></div>';
        }
    } else {
        header('Location: index.php');
    }
    echo '<div class="phdr">Всего: ' . $total . '</div>';
    echo '<p><a href="index.php?id=' . $id . '&amp;act=who' . ($do == 'guest' ? '">Показать авторизованных' : '&amp;do=guest">Показать гостей') . '</a><br /><a href="index.php?id=' . $id . '">В тему</a></p>';
} else {
    ////////////////////////////////////////////////////////////
    // Показываем общий список тех, кто в форуме              //
    ////////////////////////////////////////////////////////////
    echo '<div class="phdr"><b>Кто в форуме</b></div>';
    echo '<div class="bmenu">Список ' . ($do == 'guest' ? 'гостей' : 'авторизованных') . '</div>';
    $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `" . ($do == 'guest' ? "cms_guests" : "users") . "` WHERE `lastdate` > $onltime AND `place` LIKE 'forum%'"), 0);
    if ($total) {
        $req = mysql_query("SELECT * FROM `" . ($do == 'guest' ? "cms_guests" : "users") . "` WHERE `lastdate` > $onltime AND `place` LIKE 'forum%' ORDER BY " . ($do == 'guest' ? "`movings` DESC" : "`name` ASC") . " LIMIT $start, $kmess");
        while ($res = mysql_fetch_assoc($req)) {
            echo ($i % 2) ? '<div class="list2">' : '<div class="list1">';
            // Вычисляем местоположение
            $place = '';
            switch ($res['place']) {
                case 'forum':
                    $place = '<a href="index.php">на главной форума</a>';
                    break;

                case 'forumwho':
                    $place = 'тут, в списке';
                    break;

                case 'forumfiles':
                    $place = '<a href="index.php?act=files">смотрит файлы форума</a>';
                    break;

                case 'forumnew':
                    $place = '<a href="index.php?act=new">смотрит непрочитанное</a>';
                    break;

                case 'forumsearch':
                    $place = '<a href="search.php">поиск форума</a>';
                    break;

                case 'forumlaw':
                    $place = '<a href="index.php?act=read">читает правила форума</a>';
                    break;

                case 'forummod':
                    $place = '<a href="index.php?act=moders">смотрит список модеров</a>';
                    break;

                case 'forumfaq':
                    $place = '<a href="index.php?act=faq">смотрит FAQ</a>';
                    break;

                default:
                    $where = explode(",", $res['place']);
                    if ($where[0] == 'forum' && intval($where[1])) {
                        $req_t = mysql_query("SELECT `type`, `refid`, `text` FROM `forum` WHERE `id` = '$where[1]' LIMIT 1");
                        if (mysql_num_rows($req_t)) {
                            $res_t = mysql_fetch_assoc($req_t);
                            $link = '<a href="index.php?id=' . $where[1] . '">' . $res_t['text'] . '</a>';
                            switch ($res_t['type']) {
                                case 'f':
                                    $place = 'в категории &quot;' . $link . '&quot;';
                                    break;

                                case 'r':
                                    $place = 'в разделе &quot;' . $link . '&quot;';
                                    break;

                                case 't':
                                    $place = (isset($where[2]) ? 'пишет в тему &quot;' : 'в теме &quot;') . $link . '&quot;';
                                    break;

                                case 'm':
                                    $req_m = mysql_query("SELECT `text` FROM `forum` WHERE `id` = '" . $res_t['refid'] . "' AND `type` = 't' LIMIT 1");
                                    if (mysql_num_rows($req_m)) {
                                        $res_m = mysql_fetch_assoc($req_m);
                                        $place = (isset($where[2]) ? 'отвечает в теме' : 'в теме') . ' &quot;<a href="index.php?id=' . $res_t['refid'] . '">' . $res_m['text'] . '</a>&quot;';
                                    }
                                    break;
                            }
                        }
                    }
            }
            echo show_user($res, 0, ($act == 'guest' || ($rights >= 1 && $rights >= $res['rights']) ? 1 : 0), '<br /><img src="../images/info.png" width="16" height="16" align="middle" />&nbsp;' . $place);
            echo '</div>';
            ++$i;
        }
    } else {
        echo '<div class="menu"><p>Никого нет</p></div>';
    }
    echo '<div class="phdr">Всего: ' . $total . '</div>';
    if ($total > 10) {
        echo '<p>' . pagenav('index.php?act=who&amp;' . ($do == 'guest' ? 'do=guest&amp;' : ''), $start, $total, $kmess) . '</p>';
        echo '<p><form action="index.php?act=who' . ($do == 'guest' ? '&amp;do=guest' : '') . '" method="post"><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
    }
    echo '<p><a href="index.php?act=who' . ($do == 'guest' ? '">Показать авторизованных' : '&amp;do=guest">Показать гостей') . '</a><br /><a href="index.php">В форум</a></p>';
}

require_once('../incfiles/end.php');

?>