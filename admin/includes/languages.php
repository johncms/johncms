<?php

defined('_IN_JOHNADM') or die('Error: restricted access');

// Проверяем права доступа
if ($rights < 9) {
    header('Location: http://johncms.com/?err');
    exit;
}

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);
$panel_lng = core::load_lng('panel_lng');

// Читаем каталог с файлами языков
$lng_list = [];
$lng_desc = [];

foreach (glob('../incfiles/languages/*/_core.ini') as $val) {
    $dir = explode('/', dirname($val));
    $iso = array_pop($dir);
    $desc = parse_ini_file($val);
    $lng_list[$iso] = isset($desc['name']) && !empty($desc['name']) ? $desc['name'] : $iso;
    $lng_desc[$iso] = $desc;
}

// Автоустановка языков
if (isset($_GET['refresh'])) {
    $db->exec("DELETE FROM `cms_settings` WHERE `key` = 'lng_list'");
    core::$lng_list = [];
    echo '<div class="gmenu"><p>' . _t('Descriptions have been updated successfully') . '</p></div>';
}

$lng_add = array_diff(array_keys($lng_list), array_keys(core::$lng_list));
$lng_del = array_diff(array_keys(core::$lng_list), array_keys($lng_list));

if (!empty($lng_add) || !empty($lng_del)) {
    if (!empty($lng_del) && in_array($set['lng'], $lng_del)) {
        // Если удаленный язык был системный, то меняем на первый доступный
        $db->exec("UPDATE `cms_settings` SET `val` = '" . key($lng_list[$iso]) . "' WHERE `key` = 'lng' LIMIT 1");
    }

    $req = $db->query("SELECT * FROM `cms_settings` WHERE `key` = 'lng_list'");

    if ($req->rowCount()) {
        $db->exec("UPDATE `cms_settings` SET `val` = " . $db->quote(serialize($lng_list)) . " WHERE `key` = 'lng_list' LIMIT 1");
    } else {
        $db->exec("INSERT INTO `cms_settings` SET `key` = 'lng_list', `val` = " . $db->quote(serialize($lng_list)));
    }
}

$language = isset($_GET['language']) ? trim($_GET['language']) : false;

switch ($mod) {
    case 'set':
        // Меняем системный язык
        $iso = isset($_POST['iso']) ? trim($_POST['iso']) : false;

        if ($iso && array_key_exists($iso, $lng_list)) {
            $db->query("UPDATE `cms_settings` SET `val` = " . $db->quote($iso) . " WHERE `key` = 'lng'");
        }

        header('Location: index.php?act=languages');
        break;

    default:
        // Выводим список доступных языков
        echo '<div class="phdr"><a href="index.php"><b>' . _t('Admin Panel') . '</b></a> | ' . _t('Default language') . '</div>';

        if ($do == 'error') {
            echo '<div class="rmenu"><b>' . $panel_lng['error'] . '!</b></div>';
        }

        echo '<div class="menu"><form action="index.php?act=languages&amp;mod=set" method="post"><p>';
        echo '<table>';

        foreach ($lng_desc as $key => $val) {
            echo '<tr>' .
                '<td valign="top"><input type="radio" value="' . $key . '" name="iso" ' . ($key == $set['lng'] ? 'checked="checked"' : '') . '/></td>' .
                '<td style="padding-bottom:6px">' .
                (file_exists('../images/flags/' . $key . '.gif') ? '<img src="../images/flags/' . $key . '.gif" alt=""/>&#160;' : '') .
                '<b>' . $val['name'] . '</b>&#160;<span class="green">[' . $key . ']</span>' .
                '</td>' .
                '</tr>';
        }

        echo '<tr><td>&nbsp;</td><td><input type="submit" name="submit" value="' . _t('Save') . '" /></td></tr>' .
            '</table></p>' .
            '</form></div>' .
            '<div class="phdr">' . _t('Total') . ': <b>' . count($lng_desc) . '</b></div>' .
            '<p><a href="index.php?act=languages&amp;refresh">' . _t('Update Descriptions') . '</a><br /><a href="index.php">' . _t('Admin Panel') . '</a></p>';
}
