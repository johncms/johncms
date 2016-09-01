<?php

defined('_IN_JOHNADM') or die('Error: restricted access');

// Проверяем права доступа
if ($rights < 9) {
    header('Location: http://johncms.com/?err');
    exit;
}

echo '<div class="phdr"><a href="index.php"><b>' . _t('Admin Panel') . '</b></a> | ' . _t('System Settings') . '</div>';

if (isset($_POST['submit'])) {
    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);

    // Сохраняем настройки системы
    $stmt = $db->prepare('UPDATE `cms_settings` SET `val` = ? WHERE `key` =  ?');
    $stmt->execute([trim($_POST['skindef']), 'skindef']);
    $stmt->execute([trim($_POST['madm']), 'email']);
    $stmt->execute([intval($_POST['timeshift']), 'timeshift']);
    $stmt->execute([trim($_POST['copyright']), 'copyright']);
    $stmt->execute([preg_replace("#/$#", '', trim($_POST['homeurl'])), 'homeurl']);
    $stmt->execute([intval($_POST['flsz']), 'flsz']);
    $stmt->execute([isset($_POST['gz']), 'gzip']);
    $stmt->execute([trim($_POST['meta_key']), 'meta_key']);
    $stmt->execute([trim($_POST['meta_desc']), 'meta_desc']);

    $req = $db->query("SELECT * FROM `cms_settings`");
    $set = [];

    while ($res = $req->fetch()) {
        $set[$res[0]] = $res[1];
    }

    echo '<div class="rmenu">' . _t('Settings are saved successfully') . '</div>';
}

// Форма ввода параметров системы
echo '<form action="index.php?act=settings" method="post"><div class="menu">';

// Общие настройки
echo '<p>' .
    '<h3>' . _t('Common Settings') . '</h3>' .
    _t('Web site address without the slash at the end') . '<br>' . '<input type="text" name="homeurl" value="' . htmlentities($set['homeurl']) . '"/><br>' .
    _t('Site copyright') . '<br>' . '<input type="text" name="copyright" value="' . htmlentities($set['copyright'], ENT_QUOTES, 'UTF-8') . '"/><br>' .
    _t('Site Email') . '<br>' . '<input name="madm" maxlength="50" value="' . htmlentities($set['email']) . '"/><br>' .
    _t('Max. file size') . ' (kb):<br>' . '<input type="text" name="flsz" value="' . intval($set['flsz']) . '"/><br>' .
    '<input name="gz" type="checkbox" value="1" ' . ($set['gzip'] ? 'checked="checked"' : '') . ' />&#160;' . _t('Gzip compression') .
    '</p>';

// Настройка времени
echo '<p>' .
    '<h3>' . _t('Time shift') . '</h3>' .
    '<input type="text" name="timeshift" size="2" maxlength="3" value="' . $set['timeshift'] . '"/> (+-12)<br>' .
    '<span style="font-weight:bold; background-color:#C0FFC0">' . date("H:i", time() + $set['timeshift'] * 3600) . '</span> ' . _t('System Time') .
    '<br><span style="font-weight:bold; background-color:#FFC0C0">' . date("H:i") . '</span> ' . _t('Server Time') .
    '</p>';

// META тэги
echo '<p>' .
    '<h3>' . _t('META tags') . '</h3>' .
    '&#160;' . _t('Keywords') . '<br>&#160;<textarea rows="' . $set_user['field_h'] . '" name="meta_key">' . $set['meta_key'] . '</textarea><br>' .
    '&#160;' . _t('Description') . '<br>&#160;<textarea rows="' . $set_user['field_h'] . '" name="meta_desc">' . $set['meta_desc'] . '</textarea>' .
    '</p>';

// Выбор темы оформления
echo '<p><h3>' . _t('Themes') . '</h3>&#160;<select name="skindef">';
$dir = opendir('../theme');

while ($skindef = readdir($dir)) {
    if (($skindef != '.') && ($skindef != '..') && ($skindef != '.svn')) {
        $skindef = str_replace('.css', '', $skindef);
        echo '<option' . ($set['skindef'] == $skindef ? ' selected="selected">' : '>') . $skindef . '</option>';
    }
}

closedir($dir);

echo '</select>' .
    '</p><br><p><input type="submit" name="submit" value="' . _t('Save') . '"/></p></div></form>' .
    '<div class="phdr">&#160;</div>' .
    '<p><a href="index.php">' . _t('Admin Panel') . '</a></p>';
