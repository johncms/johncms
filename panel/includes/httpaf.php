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
    header('Location: http://johncms.com/?err');
    exit;
}

echo '<div class="phdr"><a href="index.php"><b>' . $lng['admin_panel'] . '</b></a> | HTTP Antiflood</div>';
switch ($mod){
    case 'white':
        echo '<div class="topmenu"><a href="index.php?act=httpaf">Обзор</a> | <b>Белый список</b> | <a href="index.php?act=httpaf&amp;mod=black">Черный список</a></div>';
        echo '<div class="menu"><p>';
        echo '<h3>Белый список IP адресов</h3>';
        echo '';
        echo '';
        echo '';
        echo '</p></div>';
        break;

    case 'black':
        echo '<div class="topmenu"><a href="index.php?act=httpaf">Обзор</a> | <a href="index.php?act=httpaf&amp;mod=white">Белый список</a> | <b>Черный список</b></div>';
        echo '<div class="menu"><p>';
        echo '<h3>Черный список IP адресов</h3>';
        echo '';
        echo '';
        echo '';
        echo '</p></div>';
        break;

    default:
        echo '<div class="topmenu"><b>Обзор</b> | <a href="index.php?act=httpaf&amp;mod=white">Белый список</a> | <a href="index.php?act=httpaf&amp;mod=black">Черный список</a></div>';
        echo '<div class="menu"><p>';
        echo '';
        echo '';
        echo '';
        echo '';
        echo '</p></div>';
}
echo '<div class="phdr">&#160;</div>';
  
?>