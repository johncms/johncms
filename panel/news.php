<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS v.1.1.0                     30.05.2008                             //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@gazenwagen.com                  //
// Олег Касьянов aka AlkatraZ          alkatraz@gazenwagen.com                //
//                                                                            //
// Плагиат и удаление копирайтов заруганы на ближайших родственников!!!       //
////////////////////////////////////////////////////////////////////////////////
*/

define('_IN_JOHNCMS', 1);

$textl = 'Управление новостями';
require_once ("../incfiles/core.php");

if (!$dostadm)
{
    header("Location: main.php");
    exit;
}

require_once ("../incfiles/head.php");

echo '<div class="phdr"><b>Новости на Главной</b></div>';
if (!empty($set['news']))
{
    if (isset($_POST['submit']))
    {
        $view = isset($_POST['view']) ? intval($_POST['view']) : 0;
        $size = isset($_POST['size']) ? intval($_POST['size']) : 200;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 5;
        $days = isset($_POST['days']) ? intval($_POST['days']) : 3;
        $breaks = isset($_POST['breaks']) ? intval($_POST['breaks']) : 0;
        $smileys = isset($_POST['smileys']) ? intval($_POST['smileys']) : 0;
        $tags = isset($_POST['tags']) ? intval($_POST['tags']) : 0;
        $kom = isset($_POST['kom']) ? intval($_POST['kom']) : 0;
        if ($view < 0 || $view > 3 || $size < 50 || $size > 500 || $quantity < 1 || $quantity > 15 || $days < 0 || $days > 15 || $breaks < 0 || $breaks > 1)
        {
            echo '<p>ОШИБКА<br />Значения полей выходят за допустимые пределы<br /><a href="news.php">Назад</a></p>';
            require_once ("../incfiles/end.php");
            exit;
        }
        $settings = array('view' => $view, 'size' => $size, 'quantity' => $quantity, 'days' => $days, 'breaks' => $breaks, 'smileys' => $smileys, 'tags' => $tags, 'kom' => $kom);
        mysql_query("UPDATE `cms_settings` SET `val` = '" . serialize($settings) . "' WHERE `key` = 'news'");
        header("location: news.php?set");
    } else
    {
        if (isset($_GET['set']))

        {
            echo '<div class="rmenu">Настройки сохранены</div>';
        }
        // Форма с настройками
        $settings = unserialize($set['news']);
        echo '<form action="news.php" method="post">';
        echo '<div class="menu"><u>Внешний вид</u><p>';
        echo '<input type="radio" value="1" name="view" ' . ($settings['view'] == 1 ? 'checked="checked"' : '') . '/>&nbsp;Заголовок + текст<br />';
        echo '<input type="radio" value="2" name="view" ' . ($settings['view'] == 2 ? 'checked="checked"' : '') . '/>&nbsp;Заголовок<br />';
        echo '<input type="radio" value="3" name="view" ' . ($settings['view'] == 3 ? 'checked="checked"' : '') . '/>&nbsp;Текст<br />';
        echo '<input type="radio" value="0" name="view" ' . ($settings['view'] == 0 ? 'checked="checked"' : '') . '/>&nbsp;<b>Не показывать</b></p>';
        echo '<p><input name="breaks" type="checkbox" value="1" ' . ($settings['breaks'] ? 'checked="checked"' : '') . ' />&nbsp;Переносы строк<br />';
        echo '<input name="smileys" type="checkbox" value="1" ' . ($settings['smileys'] ? 'checked="checked"' : '') . ' />&nbsp;Смайлы<br />';
        echo '<input name="tags" type="checkbox" value="1" ' . ($settings['tags'] ? 'checked="checked"' : '') . ' />&nbsp;Тэги (BBcode)<br />';
        echo '<input name="kom" type="checkbox" value="1" ' . ($settings['kom'] ? 'checked="checked"' : '') . ' />&nbsp;Комментарии</p></div>';
        echo '<div class="menu"><u>Размер текста</u><br /><input type="text" size="3" maxlength="3" name="size" value="' . $settings['size'] . '" />&nbsp;(50 - 500)</div>';
        echo '<div class="menu"><u>К-во новостей</u><br /><input type="text" size="3" maxlength="2" name="quantity" value="' . $settings['quantity'] . '" />&nbsp;(1 - 15)</div>';
        echo '<div class="menu"><u>Сколько дней показывать?</u><br /><small>0 - без ограничений</small><br /><input type="text" size="3" maxlength="2" name="days" value="' . $settings['days'] . '" />&nbsp;(0 - 15)</div>';
        echo '<div class="bmenu"><input type="submit" value="Запомнить" name="submit" /></div>';
        echo '</form>';
    }
} else
{
    $settings = serialize(array('view' => '1', 'size' => '200', 'quantity' => '5', 'days' => '3', 'breaks' => '1', 'smileys' => '0', 'tags' => '0', 'kom' => '1'));
    mysql_query("INSERT INTO `cms_settings` SET `key` = 'news', `val` = '" . $settings . "'");
    echo '<div class="menu"><p>Настройки модуля не заданы, будут использованы значения по умолчанию.<br /></p></div><div class="bmenu"><a href="news.php">Продолжить</a></div>';
}

echo '<p><a href="main.php">В Админку</a></p>';
require_once ("../incfiles/end.php");

?>