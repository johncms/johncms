<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

define('_IN_JOHNCMS', 1);

$rootpath = '';
require('incfiles/core.php');

$referer = isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : core::$system_set['homeurl'];
$url = isset($_REQUEST['url']) ? strip_tags(html_entity_decode(base64_decode(trim($_REQUEST['url'])))) : false;

if (isset($_GET['lng'])) {
    /*
    -----------------------------------------------------------------
    Переключатель языков
    -----------------------------------------------------------------
    */
    require('incfiles/head.php');
    echo '<div class="menu"><form action="' . $referer . '" method="post"><p>';
    if (count(core::$lng_list) > 1) {
        echo '<p><h3>' . $lng['language_select'] . '</h3>';
        foreach (core::$lng_list as $key => $val) {
            echo '<div><input type="radio" value="' . $key . '" name="setlng" ' . ($key == core::$lng_iso ? 'checked="checked"' : '') . '/>&#160;' .
                 (file_exists('images/flags/' . $key . '.gif') ? '<img src="images/flags/' . $key . '.gif" alt=""/>&#160;' : '') .
                 $val .
                 ($key == core::$system_set['lng'] ? ' <small class="red">[' . $lng['default'] . ']</small>' : '') .
                 '</div>';
        }
        echo '</p>';
    }
    echo '</p><p><input type="submit" name="submit" value="' . $lng['apply'] . '" /></p>' .
         '<p><a href="' . $referer . '">' . $lng['back'] . '</a></p></form></div>';
    require('incfiles/end.php');
} elseif ($url) {
    /*
    -----------------------------------------------------------------
    Редирект по ссылкам в текстах, обработанным функцией tags()
    -----------------------------------------------------------------
    */
    if (isset($_POST['submit'])) {
        header('Location: ' . $url);
    } else {
        require('incfiles/head.php');
        echo '<div class="phdr"><b>' . $lng['external_link'] . '</b></div>' .
             '<div class="rmenu">' .
             '<form action="go.php?url=' . base64_encode($url) . '" method="post">' .
             '<p>' . $lng['redirect_1'] . ':<br /><span class="red">' . functions::checkout($url) . '</span></p>' .
             '<p>' . $lng['redirect_2'] . '.<br />' .
             $lng['redirect_3'] . ' <span class="green">' . $set['homeurl'] . '</span> ' . $lng['redirect_4'] . '.</p>' .
             '<p><input type="submit" name="submit" value="' . $lng['redirect_5'] . '" /></p>' .
             '</form></div>' .
             '<div class="phdr"><a href="' . $referer . '">' . $lng['back'] . '</a></div>';
        require('incfiles/end.php');
    }
} elseif ($id) {
    /*
    -----------------------------------------------------------------
    Редирект по рекламной ссылке
    -----------------------------------------------------------------
    */
    $req = mysql_query("SELECT * FROM `cms_ads` WHERE `id` = '$id'");
    if (mysql_num_rows($req)) {
        $res = mysql_fetch_assoc($req);
        $count_link = $res['count'] + 1;
        mysql_query("UPDATE `cms_ads` SET `count` = '$count_link'  WHERE `id` = '$id'");
        header('Location: ' . $res['link']);
    } else {
        header("Location: http://johncms.com/index.php?act=404");
    }
} else {
    /*
    -----------------------------------------------------------------
    Редирект по "быстрому переходу"
    -----------------------------------------------------------------
    */
    $adres = trim($_POST['adres']);
    switch ($adres) {
        case 'forum':
            header('location: ' . core::$system_set['homeurl'] . '/forum/index.php');
            break;

        case 'lib':
            header('location: ' . core::$system_set['homeurl'] . '/library/index.php');
            break;

        case 'down':
            header('location: ' . core::$system_set['homeurl'] . '/download/index.php');
            break;

        case 'gallery':
            header('location: ' . core::$system_set['homeurl'] . '/gallery/index.php');
            break;

        case 'news':
            header('location: ' . core::$system_set['homeurl'] . '/news/index.php');
            break;

        case 'guest':
            header('location: ' . core::$system_set['homeurl'] . '/guestbook/index.php');
            break;

        case 'gazen':
            header('location: http://gazenwagen.com');
            break;

        default :
            header('location: http://johncms.com');
            break;
    }
}
?>