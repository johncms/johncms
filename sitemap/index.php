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

/*
-----------------------------------------------------------------
Формируем Карту Сайта и записываем в Кэш
-----------------------------------------------------------------
*/
function sitemap() {
    global $rootpath, $realtime, $set;
    $links_count = 140;
    $file = $rootpath . 'files/cache/sitemap.dat';
    if (file_exists($file) && filemtime($file) > ($realtime - 604800)) {
        // Считываем ссылки из Кэша
        return file_get_contents($file);
    } else {
        $out = '';
        // Карта Форума
        $req = mysql_query("SELECT * FROM `forum` WHERE `type` = 'r'");
        if (mysql_num_rows($req)) {
            $out .= '<b>Forum Map</b>' . "\r\n";
            while ($res = mysql_fetch_assoc($req)) {
                $count = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `refid` = '" . $res['id'] . "' AND `type` = 't' AND `close` != '1'"), 0);
                if ($count) {
                    $text = html_entity_decode($res['text']);
                    $text = mb_substr($text, 0, 30);
                    // Подсчитываем число блоков ссылок
                    $pages = ceil($count / $links_count);
                    if($pages > 1){
                        for($i = 0; $i < $pages; $i++){
                            $out .= '<br /><a href="' . $set['homeurl'] . '/sitemap/forum.php?id=' . $res['id'] . '&amp;p=' . $i . '">' . functions::checkout($text) . ' (part ' . ($i + 1) . ')</a>' . "\r\n";
                        }
                    } else {
                        $out .= '<br /><a href="' . $set['homeurl'] . '/sitemap/forum.php?id=' . $res['id'] . '">' . functions::checkout($text) . '</a>' . "\r\n";
                    }
                }
            }
        }
        // Карта Библиотеки
        $req = mysql_query("SELECT * FROM `lib` WHERE `type` = 'cat' AND `ip` = '0'");
        if (mysql_num_rows($req)) {
            $out .= '<br /><br /><b>Library Map</b>' . "\r\n";
            while ($res = mysql_fetch_assoc($req)) {
                $text = html_entity_decode($res['text']);
                $text = mb_substr($text, 0, 30);
                $out .= '<br /><a href="../library/index.php?id=' . $res['id'] . '">' . functions::checkout($text) . '</a>' . "\r\n";
            }
        }
        if (!empty($out)) {
            // записываем Кэш ссылок
            if (!file_put_contents($file, $out)) {
                return 'Cache file write error!';
            }
            return $out;
        } else {
            return false;
        }
    }
}

/*
-----------------------------------------------------------------
Показываем карту сайта
-----------------------------------------------------------------
*/
if (!defined('_IN_JOHNCMS')) {
    define('_IN_JOHNCMS', 1);
    require('../incfiles/core.php');
    require('../incfiles/head.php');
    echo '<div class="menu">' . sitemap() . '</div>';
    require('../incfiles/end.php');
} else {
        echo '<div class="menu"><div class="sitemap">' . sitemap() . '</div></div>';
}
?>