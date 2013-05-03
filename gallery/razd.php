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
        mysql_query("insert into `gallery` values(0,'0','" . time() . "','rz','','" . $text . "','','" . $user . "','','');");
        header("location: index.php");
    } else {
        echo '<div class="phdr"><b>' . $lng_gal['create_section'] . '</b></div>' .
            '<form action="index.php?act=razd" method="post">' .
            '<div class="gmenu">' .
            '<p>' . $lng['name'] . ':<br/><input type="text" name="text"/></p>' .
            '<p><input type="submit" name="submit" value="' . $lng['save'] . '"/></p>' .
            '</div></form>' .
            '<div class="phdr"><a href="index.php">' . $lng['back'] . '</a></div>';
    }
}