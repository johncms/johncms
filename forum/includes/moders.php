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

defined('_IN_JOHNCMS') or die('Error: restricted access');

require('../incfiles/head.php');
echo '<div class="phdr"><a href="index.php"><b>' . $lng['forum'] . '</b></a> | ' . $lng['moders'] . '</div>';
$req = mysql_query("SELECT * FROM `forum` WHERE `type` = 'f' ORDER BY `realid`");
while ($f1 = mysql_fetch_array($req)) {
    $mod = mysql_query("select * from `forum` where type='a' and refid='" . $f1['id'] . "'");
    $mod2 = mysql_num_rows($mod);
    if ($mod2 != 0) {
        echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
        echo '<b>' . $f1['text'] . '</b><br />';
        while ($mod1 = mysql_fetch_array($mod)) {
            $uz = mysql_query("select * from `users` where name='" . $mod1['from'] . "';");
            $uz1 = mysql_fetch_array($uz);
            if ($uz1['rights'] == 3) {
                if ((!empty($_SESSION['uid'])) && ($login != $mod1['from'])) {
                    echo '<a href="../users/profile.php?user=' . $uz1['id'] . '">' . $mod1['from'] . '</a>';
                } else {
                    echo $mod1['from'];
                }
            }
        }
        echo '</div>';
        ++$i;
    }
}
echo '<div class="phdr"><a href="index.php?id=' . $id . '">' . $lng['back'] . '</a></div>';
?>