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

require_once("../incfiles/head.php");
if ($rights == 4 || $rights >= 6) {
    $cat = isset($_GET['cat']) ? abs(intval($_GET['cat'])) : 0;

    if (isset ($_POST['submit'])) {
        if (empty ($cat)) {
            $droot = $loadroot;
        } else {
            provcat($cat);
            $cat1 = mysql_query("select * from `download` where type = 'cat' and id = '" . $cat . "';");
            $adrdir = mysql_fetch_array($cat1);
            $droot = "$adrdir[adres]/$adrdir[name]";
        }
        $drn = functions::check($_POST['drn']);
        $rusn = functions::check($_POST['rusn']);
        $mk = mkdir("$droot/$drn", 0777);
        if ($mk == true) {
            chmod("$droot/$drn", 0777);
            echo "Папка создана<br/>";
            mysql_query("INSERT INTO `download` SET
              `refid` = $cat,
              `adres` = '" . mysql_real_escape_string($droot) . "',
              `time` = " . time() . ",
              `name` = '$drn',
              `type` = 'cat',
              `ip` = '',
              `soft` = '',
              `text` = '$rusn',
              `screen` = ''
              ");
            $categ = mysql_query("select * from `download` where type = 'cat' and name='$drn' and refid = '" . $cat . "';");
            $newcat = mysql_fetch_array($categ);
            echo "&#187;<a href='?cat=" . $newcat['id'] . "'>В папку</a><br/>";
        } else {
            echo "ERROR<br/>";
        }
    } else {
        echo "<form action='?act=makdir&amp;cat=" . intval($_GET['cat']) . "' method='post'>
         <p>" . $lng_dl['folder_name'] . "<br />
         <input type='text' name='drn'/></p>
         <p>" . $lng_dl['folder_name_for_list'] . ":<br/>
         <input type='text' name='rusn'/></p>
         <p><input type='submit' name='submit' value='Создать'/></p>
         </form>";
    }
}
echo "<a href='?'>" . $lng['back'] . "</a><br/>";