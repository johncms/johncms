<?php

defined('_IN_JOHNADM') or die('Error: restricted access');

// Проверяем права доступа
if ($rights < 7) {
    header('Location: http://johncms.com/?err');
    exit;
}

echo '<div class="phdr"><a href="index.php"><b>' . _t('Admin Panel') . '</b></a> | ' . _t('Permissions') . '</div>';

if (isset($_POST['submit'])) {
    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);

    // Записываем настройки в базу
    $stmt = $db->prepare('REPLACE `cms_settings` SET `val` = ?, key` = ?');

    $stmt->execute([(isset($_POST['reg']) ? intval($_POST['reg']) : 0), 'mod_reg']);
    $stmt->execute([(isset($_POST['forum']) ? intval($_POST['forum']) : 0), 'mod_forum']);
    $stmt->execute([(isset($_POST['guest']) ? intval($_POST['guest']) : 0), 'mod_guest']);
    $stmt->execute([(isset($_POST['lib']) ? intval($_POST['lib']) : 0), 'mod_lib']);
    $stmt->execute([(isset($_POST['down']) ? intval($_POST['down']) : 0), 'mod_down']);
    $stmt->execute([isset($_POST['libcomm']), 'mod_lib_comm']);
    $stmt->execute([isset($_POST['downcomm']), 'mod_down_comm']);
    $stmt->execute([(isset($_POST['active']) ? intval($_POST['active']) : 0), 'active']);
    $stmt->execute([(isset($_POST['access']) ? intval($_POST['access']) : 0), 'site_access']);

    $req = $db->query('SELECT * FROM `cms_settings`');
    $set = [];

    while ($res = $req->fetch()) {
        $set[$res[0]] = $res[1];
    }

    echo '<div class="rmenu">' . _t('Settings are saved successfully') . '</div>';
}

$color = ['red', 'yelow', 'green', 'gray'];
echo '<form method="post" action="index.php?act=access">';

// Управление доступом к Форуму
echo '<div class="menu"><p>' .
    '<h3><img src="../images/' . $color[$set['mod_forum']] . '.gif" width="16" height="16" class="left"/>&#160;' . _t('Forum') . '</h3>' .
    '<div style="font-size: x-small">' .
    '<input type="radio" value="2" name="forum" ' . ($set['mod_forum'] == 2 ? 'checked="checked"' : '') . '/>&#160;' . _t('Access is allowed') . '<br>' .
    '<input type="radio" value="1" name="forum" ' . ($set['mod_forum'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . _t('Only for authorized') . '<br>' .
    '<input type="radio" value="3" name="forum" ' . ($set['mod_forum'] == 3 ? 'checked="checked"' : '') . '/>&#160;' . _t('Read only') . '<br>' .
    '<input type="radio" value="0" name="forum" ' . (!$set['mod_forum'] ? 'checked="checked"' : '') . '/>&#160;' . _t('Access denied') .
    '</div></p>';

// Управление доступом к Гостевой
echo '<p><h3><img src="../images/' . $color[$set['mod_guest']] . '.gif" width="16" height="16" class="left"/>&#160;' . _t('Guestbook') . '</h3>' .
    '<div style="font-size: x-small">' .
    '<input type="radio" value="2" name="guest" ' . ($set['mod_guest'] == 2 ? 'checked="checked"' : '') . '/>&#160;' . _t('Access is allowed') . '<br>' .
    '<input type="radio" value="1" name="guest" ' . ($set['mod_guest'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . _t('Only for authorized') . '<br>' .
    '<input type="radio" value="0" name="guest" ' . (!$set['mod_guest'] ? 'checked="checked"' : '') . '/>&#160;' . _t('Access denied') .
    '</div></p>';

// Управление доступом к Библиотеке
echo '<p><h3><img src="../images/' . $color[$set['mod_lib']] . '.gif" width="16" height="16" class="left"/>&#160;' . _t('Library') . '</h3>' .
    '<div style="font-size: x-small">' .
    '<input type="radio" value="2" name="lib" ' . ($set['mod_lib'] == 2 ? 'checked="checked"' : '') . '/>&#160;' . _t('Access is allowed') . '<br>' .
    '<input type="radio" value="1" name="lib" ' . ($set['mod_lib'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . _t('Only for authorized') . '<br>' .
    '<input type="radio" value="0" name="lib" ' . (!$set['mod_lib'] ? 'checked="checked"' : '') . '/>&#160;' . _t('Access denied') . '<br>' .
    '<input name="libcomm" type="checkbox" value="1" ' . ($set['mod_lib_comm'] ? 'checked="checked"' : '') . ' />&#160;' . _t('Comments') .
    '</div></p>';

// Управление доступом к Загрузкам
echo '<p><h3><img src="../images/' . $color[$set['mod_down']] . '.gif" width="16" height="16" class="left"/>&#160;' . _t('Downloads') . '</h3>' .
    '<div style="font-size: x-small">' .
    '<input type="radio" value="2" name="down" ' . ($set['mod_down'] == 2 ? 'checked="checked"' : '') . '/>&#160;' . _t('Access is allowed') . '<br>' .
    '<input type="radio" value="1" name="down" ' . ($set['mod_down'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . _t('Only for authorized') . '<br>' .
    '<input type="radio" value="0" name="down" ' . (!$set['mod_down'] ? 'checked="checked"' : '') . '/>&#160;' . _t('Access denied') . '<br>' .
    '<input name="downcomm" type="checkbox" value="1" ' . ($set['mod_down_comm'] ? 'checked="checked"' : '') . ' />&#160;' . _t('Comments') .
    '</div></p>';

// Управление доступом к Активу сайта (списки юзеров и т.д.)
echo '<p><h3><img src="../images/' . $color[$set['active'] + 1] . '.gif" width="16" height="16" class="left"/>&#160;' . _t('Community') . '</h3>' .
    '<div style="font-size: x-small">' .
    '<input type="radio" value="1" name="active" ' . ($set['active'] ? 'checked="checked"' : '') . '/>&#160;' . _t('Access is allowed') . '<br>' .
    '<input type="radio" value="0" name="active" ' . (!$set['active'] ? 'checked="checked"' : '') . '/>&#160;' . _t('Only for authorized') . '<br>' .
    '</div></p></div>';

// Управление доступом к Регистрации
echo '<div class="gmenu"><h3><img src="../images/' . $color[$set['mod_reg']] . '.gif" width="16" height="16" class="left"/>&#160;' . _t('Registration') . '</h3>' .
    '<div style="font-size: x-small">' .
    '<input type="radio" value="2" name="reg" ' . ($set['mod_reg'] == 2 ? 'checked="checked"' : '') . '/>&#160;' . _t('Access is allowed') . '<br>' .
    '<input type="radio" value="1" name="reg" ' . ($set['mod_reg'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . _t('With moderation') . '<br>' .
    '<input type="radio" value="0" name="reg" ' . (!$set['mod_reg'] ? 'checked="checked"' : '') . '/>&#160;' . _t('Access denied') .
    '</div></div>';

// Управление доступом к Сайту (Закрытие сайта)
echo '<div class="rmenu">' .
    '<h3><img src="../images/' . $color[$set['site_access']] . '.gif" width="16" height="16" class="left"/>&#160;' . _t('Site access') . '</h3>' .
    '<div style="font-size: x-small">' .
    '<input class="btn btn-large" type="radio" value="2" name="access" ' . ($set['site_access'] == 2 ? 'checked="checked"' : '') . '/>&#160;' . _t('Access is allowed') . '<br>' .
    '<input class="btn btn-large" type="radio" value="1" name="access" ' . ($set['site_access'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . _t('It is closed for all, except Administration') . '<br>' .
    '<input class="btn btn-large" type="radio" value="0" name="access" ' . (!$set['site_access'] ? 'checked="checked"' : '') . '/>&#160;' . _t('It is closed for all, except SV') . '<br>' .
    '</div></div>';

echo '<div class="phdr"><small>' . _t('Administrators always have access to all closed modules and comments') . '</small></div>' .
    '<p><input type="submit" name="submit" id="button" value="' . _t('Save') . '" /></p>' .
    '<p><a href="index.php">' . _t('Admin Panel') . '</a></p></form>';
