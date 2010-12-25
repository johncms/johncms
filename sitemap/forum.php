<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                Mobile Content Management System                    //
// Project site:          http://johncms.com                                  //
// Support site:          http://gazenwagen.com                               //
////////////////////////////////////////////////////////////////////////////////
// Lead Developer:        Oleg Kasyanov   (AlkatraZ)  alkatraz@gazenwagen.com //
// Development Team:      Eugene Ryabinin (john77)    john77@gazenwagen.com   //
//                        Dmitry Liseenko (FlySelf)   flyself@johncms.com     //
////////////////////////////////////////////////////////////////////////////////
*/

define('_IN_JOHNCMS', 1);
require('../incfiles/core.php');
require('../incfiles/head.php');

/*
-----------------------------------------------------------------
Список тем форума, для Карты сайта
-----------------------------------------------------------------
*/
$links_count = 140;
$p = isset($_GET['p']) ? abs(intval($_GET['p'])) : 0;
$page = $links_count * $p;
if ($id) {
    $file = $rootpath . 'files/cache/sitemap_f_' . $id . ($p ? '_' . $p : '') . '.dat';
    if (file_exists($file) && filemtime($file) > ($realtime - 604800)) {
        // Считываем ссылки из Кэша
        echo file_get_contents($file);
    } else {
        $out = '';
        // Проверяем существование раздела
        $req_s = mysql_query("SELECT * FROM `forum` WHERE `id` = '$id' AND `type` = 'r'");
        if (mysql_num_rows($req_s)) {
            $res_s = mysql_fetch_assoc($req_s);
            // Запрос на список тем выбранного раздела
            $req_t = mysql_query("SELECT * FROM `forum` WHERE `refid` = '$id' AND `type` = 't' AND `close` != '1' ORDER BY `time` DESC LIMIT $page, $links_count");
            while ($res_t = mysql_fetch_assoc($req_t)) {
                // Проверяем дату первого поста темы
                $req_m = mysql_query("SELECT * FROM `forum` WHERE `refid` = '" . $res_t['id'] . "' AND `type` = 'm' AND `close` != '1' ORDER BY `id` ASC LIMIT 1");
                $res_m = mysql_fetch_assoc($req_m);
                if ($res_m['time'] < $realtime - 2592000)
                    $out .= '<div><a href="' . $set['homeurl'] . '/forum/index.php?id=' . $res_t['id'] . '">' . $res_t['text'] . '</a></div>';
            }
            if (!empty($out)) {
                $out = '<div class="phdr"><b>Карта Форума</b> | ' . $res_s['text'] . '</div><div class="menu">' . $out . '</div>';
                if (!file_put_contents($file, $out)) {
                    return 'Cache file write error!';
                }
                echo $out;
            } else {
                // Если список пуст, выводим сообщение
                echo $lng['list_empty'];
            }
        } else {
            echo functions::display_error($lng['error_wrong_data']);
        }
    }
} else {
    echo functions::display_error($lng['error_wrong_data']);
}

require('../incfiles/end.php');
?>