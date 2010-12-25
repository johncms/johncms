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

defined('_IN_JOHNADM') or die('Error: restricted access');

// Проверяем права доступа
if ($rights < 7) {
    header('Location: http://johncms.com/?err');
    exit;
}

echo '<div class="phdr"><a href="index.php"><b>' . $lng['admin_panel'] . '</b></a> | ' . $lng['news_on_frontpage'] . '</div>';
if (!empty($set['news'])) {
    if (isset($_POST['submit'])) {
        $view = isset($_POST['view']) ? intval($_POST['view']) : 0;
        $size = isset($_POST['size']) ? intval($_POST['size']) : 200;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 5;
        $days = isset($_POST['days']) ? intval($_POST['days']) : 3;
        $breaks = isset($_POST['breaks']) ? intval($_POST['breaks']) : 0;
        $smileys = isset($_POST['smileys']) ? intval($_POST['smileys']) : 0;
        $tags = isset($_POST['tags']) ? intval($_POST['tags']) : 0;
        $kom = isset($_POST['kom']) ? intval($_POST['kom']) : 0;
        if ($view < 0 || $view > 3 || $size < 50 || $size > 500 || $quantity < 1 || $quantity > 15 || $days < 0 || $days > 15 || $breaks < 0 || $breaks > 1) {
            echo functions::display_error($lng['error_wrong_limits'], '<a href="index.php?act=news">' . $lng['back'] . '</a>');
            require('../incfiles/end.php');
            exit;
        }
        $settings = array (
            'view' => $view,
            'size' => $size,
            'quantity' => $quantity,
            'days' => $days,
            'breaks' => $breaks,
            'smileys' => $smileys,
            'tags' => $tags,
            'kom' => $kom
        );
        mysql_query("UPDATE `cms_settings` SET `val` = '" . serialize($settings) . "' WHERE `key` = 'news'");
        header("location: index.php?act=news&set");
    } else {
        if (isset($_GET['set'])) {
            echo '<div class="rmenu">' . $lng['settings_saved'] . '</div>';
        }
        // Форма с настройками
        $settings = unserialize($set['news']);
        echo '<form action="index.php?act=news" method="post">' .
            '<div class="menu"><p><h3>' . $lng['apperance'] . '</h3>' .
            '&#160;<input type="radio" value="1" name="view" ' . ($settings['view'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . $lng['heading_and_text'] . '<br />' .
            '&#160;<input type="radio" value="2" name="view" ' . ($settings['view'] == 2 ? 'checked="checked"' : '') . '/>&#160;' . $lng['heading'] . '<br />' .
            '&#160;<input type="radio" value="3" name="view" ' . ($settings['view'] == 3 ? 'checked="checked"' : '') . '/>&#160;' . $lng['text'] . '<br />' .
            '&#160;<input type="radio" value="0" name="view" ' . ($settings['view'] == 0 ? 'checked="checked"' : '') . '/>&#160;<b>' . $lng['dont_display'] . '</b></p>' .
            '<p>&#160;<input name="breaks" type="checkbox" value="1" ' . ($settings['breaks'] ? 'checked="checked"' : '') . ' />&#160;' . $lng['line_foldings'] . '<br />' .
            '&#160;<input name="smileys" type="checkbox" value="1" ' . ($settings['smileys'] ? 'checked="checked"' : '') . ' />&#160;' . $lng['smileys'] . '<br />' .
            '&#160;<input name="tags" type="checkbox" value="1" ' . ($settings['tags'] ? 'checked="checked"' : '') . ' />&#160;' . $lng['bbcode'] . '<br />' .
            '&#160;<input name="kom" type="checkbox" value="1" ' . ($settings['kom'] ? 'checked="checked"' : '') . ' />&#160;' . $lng['comments'] . '</p>' .
            '<p><h3>' . $lng['text_size'] . '</h3>&#160;' .
            '<input type="text" size="3" maxlength="3" name="size" value="' . $settings['size'] . '" />&#160;(50 - 500)</p>' .
            '<p><h3>' . $lng['news_count'] . '</h3>&#160;' .
            '<input type="text" size="3" maxlength="2" name="quantity" value="' . $settings['quantity'] . '" />&#160;(1 - 15)</p>' .
            '<p><h3>' . $lng['news_howmanydays_display'] . '</h3><input type="text" size="3" maxlength="2" name="days" value="' . $settings['days'] . '" />&#160;(0 - 15)<br />' .
            '<small>0 - ' . $lng['without_limit'] . '</small></p>' .
            '<p><input type="submit" value="' . $lng['save'] . '" name="submit" /></p></div>' .
            '<div class="phdr"><a href="../news/index.php">' . $lng['to_news'] . '</a></div>' .
            '</form>';
    }
} else {
    $settings = serialize(array (
        'view' => '1',
        'size' => '200',
        'quantity' => '5',
        'days' => '3',
        'breaks' => '1',
        'smileys' => '0',
        'tags' => '0',
        'kom' => '1'
    ));
    mysql_query("INSERT INTO `cms_settings` SET `key` = 'news', `val` = '" . $settings . "'");
    echo '<div class="menu"><p>' . $lng['module_default_settings'] . '<br /></p></div>' .
        '<div class="bmenu"><a href="index.php?act=news">' . $lng['continue'] . '</a></div>';
}
echo '<p><a href="index.php">' . $lng['admin_panel'] . '</a></p>';
?>