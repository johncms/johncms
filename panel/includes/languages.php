<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

defined('_IN_JOHNADM') or die('Error: restricted access');

// Проверяем права доступа
if ($rights < 9) {
    header('Location: http://johncms.com/?err');
    exit;
}
echo '<div class="phdr"><a href="index.php"><b>' . $lng['admin_panel'] . '</b></a> | ' . $lng['language_default'] . '</div>';

/*
-----------------------------------------------------------------
Читаем каталог с файлами языков
-----------------------------------------------------------------
*/
$lng_list = array();
$lng_desc = array();
foreach (glob('../incfiles/languages/*/_core.ini') as $val) {
    $dir = explode('/', dirname($val));
    $iso = array_pop($dir);
    $desc = parse_ini_file($val);
    $lng_list[$iso] = isset($desc['name']) && !empty($desc['name']) ? $desc['name'] : $iso;
    $lng_desc[$iso] = $desc;
}

/*
-----------------------------------------------------------------
Автоустановка языков
-----------------------------------------------------------------
*/
if(isset($_GET['refresh'])){
    mysql_query("DELETE FROM `cms_settings` WHERE `key` = 'lng_list'");
    core::$lng_list = array();
    echo '<div class="gmenu"><p>' . $lng['refresh_descriptions_ok'] . '</p></div>';
}
$lng_add = array_diff(array_keys($lng_list), core::$lng_list);
$lng_del = array_diff(core::$lng_list, array_keys($lng_list));
if (!empty($lng_add) || !empty($lng_del)) {
    if (!empty($lng_del) && in_array($set['lng'], $lng_del)) {
        // Если удаленный язык был системный, то меняем на первый доступный
        mysql_query("UPDATE `cms_settings` SET `val` = '" . key($lng_list[0]) . "' WHERE `key` = 'lng' LIMIT 1");
    }
    $req = mysql_query("SELECT * FROM `cms_settings` WHERE `key` = 'lng_list'");
    if (mysql_num_rows($req)) {
        mysql_query("UPDATE `cms_settings` SET `val` = '" . serialize($lng_list) . "' WHERE `key` = 'lng_list' LIMIT 1");
    } else {
        mysql_query("INSERT INTO `cms_settings` SET `key` = 'lng_list', `val` = '" . serialize($lng_list) . "'");
    }
}

switch ($mod) {
    case 'set':
        /*
        -----------------------------------------------------------------
        Меняем системный язык
        -----------------------------------------------------------------
        */
        $iso = isset($_POST['iso']) ? trim($_POST['iso']) : false;
        if ($iso && array_key_exists($iso, $lng_list)) {
            mysql_query("UPDATE `cms_settings` SET `val` = '" . mysql_real_escape_string($iso) . "' WHERE `key` = 'lng'");
        }
        header('Location: index.php?act=languages');
        break;

    default:
        /*
        -----------------------------------------------------------------
        Выводим список доступных языков
        -----------------------------------------------------------------
        */
        echo '<div class="menu"><form action="index.php?act=languages&amp;mod=set" method="post"><p>';
        echo '<table><tr><td>&nbsp;</td><td style="padding-bottom:4px"><h3>' . $lng['language_system'] . '</h3></td></tr>';
        foreach ($lng_desc as $key => $val) {
            $lng_menu = array(
                (!empty($val['author']) ? '<span class="gray">' . $lng['author'] . ':</span> ' . $val['author'] : ''),
                (!empty($val['author_email']) ? '<span class="gray">E-mail:</span> ' . $val['author_email'] : ''),
                (!empty($val['author_url']) ? '<span class="gray">URL:</span> ' . $val['author_url'] : ''),
                (!empty($val['description']) ? '<span class="gray">' . $lng['description'] . ':</span> ' . $val['description'] : '')
            );
            echo '<tr>' .
                 '<td valign="top"><input type="radio" value="' . $key . '" name="iso" ' . ($key == $set['lng'] ? 'checked="checked"' : '') . '/></td>' .
                 '<td style="padding-bottom:6px">' .
                 (file_exists('../images/flags/' . $key . '.gif') ? '<img src="../images/flags/' . $key . '.gif" alt=""/>&#160;' : '') .
                 '<b>' . $val['name'] . '</b>&#160;<span class="green">[' . $key . ']</span>' .
                 '<div class="sub">' . functions::display_menu($lng_menu, '<br />') . '</div></td>' .
                 '</tr>';
        }
        echo '<tr><td>&nbsp;</td><td><input type="submit" name="submit" value="' . $lng['save'] . '" /></td></tr>' .
             '</table></p>' .
             '</form></div><div class="phdr">' . $lng['total'] . ': ' . count($lng_desc) . '</div>' .
             '<p><a href="index.php?act=languages&amp;refresh">' . $lng['refresh_descriptions'] . '</a><br /><a href="index.php">' . $lng['admin_panel'] . '</a></p>';
}
?>