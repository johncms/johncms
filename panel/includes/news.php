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
if ($rights < 7) {
    header('Location: ' . $set['homeurl'] . '/?err'); exit;
}
echo '<div class="phdr"><a href="index.php"><b>' . $lng['admin_panel'] . '</b></a> | ' . $lng['news_on_frontpage'] . '</div>';

/*
-----------------------------------------------------------------
Настройки Новостей
-----------------------------------------------------------------
*/
if (!isset($set['news']) || isset($_GET['reset'])) {
    // Задаем настройки по умолчанию
    $settings = array (
        'view' => '1',
        'size' => '200',
        'quantity' => '3',
        'days' => '7',
        'breaks' => '1',
        'smileys' => '0',
        'tags' => '1',
        'kom' => '1'
    );
    $stmt = $db->prepare("UPDATE `cms_settings` SET
        `val` = ?
        WHERE `key` = 'news'
    ");
    $stmt->execute([
        serialize($settings)
    ]);
    echo '<div class="rmenu"><p>' . $lng['settings_default'] . '</p></div>';
} elseif (isset($_POST['submit'])) {
    // Принимаем настройки из формы
    $settings['view'] = isset($_POST['view']) && $_POST['view'] >= 0 && $_POST['view'] < 4 ? intval($_POST['view']) : 1;
    $settings['size'] = isset($_POST['size']) && $_POST['size'] >= 50 && $_POST['size'] < 500 ? intval($_POST['size']) : 200;
    $settings['quantity'] = isset($_POST['quantity']) && $_POST['quantity'] > 0 && $_POST['quantity'] < 16 ? intval($_POST['quantity']) : 3;
    $settings['days'] = isset($_POST['days']) && $_POST['days'] >= 0 && $_POST['days'] < 16 ? intval($_POST['days']) : 7;
    $settings['breaks'] = isset($_POST['breaks']);
    $settings['smileys'] = isset($_POST['smileys']);
    $settings['tags'] = isset($_POST['tags']);
    $settings['kom'] = isset($_POST['kom']);
    $stmt = $db->prepare("UPDATE `cms_settings` SET
        `val` = ?
        WHERE `key` = 'news'
    ");
    $stmt->execute([
        serialize($settings)
    ]);
    echo '<div class="gmenu"><p>' . $lng['settings_saved'] . '</p></div>';
} else {
    // Получаем сохраненные настройки
    $settings = unserialize($set['news']);
}

/*
-----------------------------------------------------------------
Форма ввода настроек
-----------------------------------------------------------------
*/
echo '<form action="index.php?act=news" method="post"><div class="menu"><p>' .
    '<h3>' . $lng['apperance'] . '</h3>' .
    '<input type="radio" value="1" name="view" ' . ($settings['view'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . $lng['heading_and_text'] . '<br />' .
    '<input type="radio" value="2" name="view" ' . ($settings['view'] == 2 ? 'checked="checked"' : '') . '/>&#160;' . $lng['heading'] . '<br />' .
    '<input type="radio" value="3" name="view" ' . ($settings['view'] == 3 ? 'checked="checked"' : '') . '/>&#160;' . $lng['text'] . '<br />' .
    '<input type="radio" value="0" name="view" ' . (!$settings['view'] ? 'checked="checked"' : '') . '/>&#160;<b>' . $lng['dont_display'] . '</b></p>' .
    '<p><input name="breaks" type="checkbox" value="1" ' . ($settings['breaks'] ? 'checked="checked"' : '') . ' />&#160;' . $lng['line_foldings'] . '<br />' .
    '<input name="smileys" type="checkbox" value="1" ' . ($settings['smileys'] ? 'checked="checked"' : '') . ' />&#160;' . $lng['smileys'] . '<br />' .
    '<input name="tags" type="checkbox" value="1" ' . ($settings['tags'] ? 'checked="checked"' : '') . ' />&#160;' . $lng['bbcode'] . '<br />' .
    '<input name="kom" type="checkbox" value="1" ' . ($settings['kom'] ? 'checked="checked"' : '') . ' />&#160;' . $lng['comments'] . '</p>' .
    '<p><h3>' . $lng['text_size'] . '</h3>&#160;' .
    '<input type="text" size="3" maxlength="3" name="size" value="' . $settings['size'] . '" />&#160;(50 - 500)</p>' .
    '<p><h3>' . $lng['news_count'] . '</h3>&#160;' .
    '<input type="text" size="3" maxlength="2" name="quantity" value="' . $settings['quantity'] . '" />&#160;(1 - 15)</p>' .
    '<p><h3>' . $lng['news_howmanydays_display'] . '</h3><input type="text" size="3" maxlength="2" name="days" value="' . $settings['days'] . '" />&#160;(0 - 15)<br />' .
    '<small>0 - ' . $lng['without_limit'] . '</small></p>' .
    '<p><input type="submit" value="' . $lng['save'] . '" name="submit" /></p></div>' .
    '<div class="phdr"><a href="index.php?act=news&amp;reset">' . $lng['reset_settings'] . '</a>' .
    '</div></form>' .
    '<p><a href="index.php">' . $lng['admin_panel'] . '</a><br />' .
    '<a href="../news/index.php">' . $lng['to_news'] . '</a></p>';
?>
