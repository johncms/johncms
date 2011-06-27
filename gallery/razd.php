<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

if ($rights >= 6) {
    if (isset($_POST['submit'])) {
        $user = 0;
        $text = functions::check($_POST['text']);
        //TODO: Переделать запрос, убрать быдлокод
        mysql_query("insert into `gallery` values(0,'0','" . time() . "','rz','','" . $text . "','','" . $user . "','','');");
        header("location: index.php");
    } else {
        echo 'Добавление раздела.<br/>
        <form action="index.php?act=razd" method="post">
        Введите название:<br/><input type="text" name="text"/><br/>
        <input type="submit" name="submit" value="Ok!"/>
        </form>
        <br/><a href="index.php">В галерею</a><br/>';
    }
} else {
    header("location: index.php");
}

?>