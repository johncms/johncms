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

$headmod = 'mail';
$textl = $lng['mail'];
require_once('../incfiles/head.php');

echo '<div class="phdr"><b>' . $lng_mail['blocklist'] . '</b></div>';
if (isset($_GET['del'])) {
    if ($id) {
        //Проверяем существование пользователя
        $req = mysql_query("SELECT * FROM `users` WHERE `id` = '$id' LIMIT 1");
        if (mysql_num_rows($req) == 0) {
            echo functions::display_error($lng['error_user_not_exist']);
            require_once("../incfiles/end.php");
            exit;
        }
        //Удаляем из заблокированных
        if (isset($_POST['submit'])) {
            $q = mysql_query("SELECT * FROM `cms_contact` WHERE `user_id`='" . $user_id . "' AND `from_id`='" . $id . "' AND `ban`='1'");
            if (mysql_num_rows($q) == 0) {
                echo '<div class="rmenu">' . $lng_mail['user_not_block'] . '</div>';
            } else {
                mysql_query("UPDATE `cms_contact` SET `ban`='0' WHERE `user_id`='$user_id' AND `from_id`='$id' AND `ban`='1';");
                echo '<div class="rmenu">' . $lng_mail['user_enabled'] . '</div>';
            }
        } else {
            echo '<div class="gmenu"><form action="index.php?act=ignor&amp;id=' . $id . '&amp;del" method="post"><div>
			' . $lng_mail['really_enabled_contact'] . '<br />
			<input type="submit" name="submit" value="' . $lng_mail['enabled'] . '"/>
			</div></form></div>';
        }
    } else {
        echo functions::display_error($lng_mail['no_contact_is_chose']);
    }
} else if (isset($_GET['add'])) {
    if ($id) {
        $req = mysql_query("SELECT * FROM `users` WHERE `id` = '$id' LIMIT 1;");
        if (mysql_num_rows($req) == 0) {
            echo functions::display_error($lng['error_user_not_exist']);
            require_once("../incfiles/end.php");
            exit;
        }
        $res = mysql_fetch_assoc($req);
        //Добавляем в заблокированные
        if (isset($_POST['submit'])) {
            if ($res['rights'] > $rights) {
                echo '<div class="rmenu">' . $lng_mail['user_impossible_block'] . '</div>';
            } else {
                $q = mysql_query("SELECT * FROM `cms_contact`
				WHERE `user_id`='" . $user_id . "' AND `from_id`='" . $id . "';");
                if (mysql_num_rows($q) == 0) {
                    mysql_query("INSERT INTO `cms_contact` SET
					`user_id` = '" . $user_id . "',
					`from_id` = '" . $id . "',
					`time` = '" . time() . "',
					`ban`='1';");
                } else {
                    mysql_query("UPDATE `cms_contact` SET `ban`='1', `friends`='0' WHERE `user_id`='$user_id' AND `from_id`='$id';");
                    mysql_query("UPDATE `cms_contact` SET `friends`='0' WHERE `user_id`='$id' AND `from_id`='$user_id';");
                }
                echo '<div class="rmenu">' . $lng_mail['user_block'] . '</div>';
            }
        } else {
            echo '<div class="rmenu"><form action="index.php?act=ignor&amp;id=' . $id . '&amp;add" method="post">
			<p>' . $lng_mail['really_block_contact'] . '</p>
			<p><input type="submit" name="submit" value="' . $lng_mail['block'] . '"/></p>
			</form></div>';
            echo '<div class="phdr"><a href="' . (isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : 'index.php') . '">' . $lng['back'] . '</a></div>';
        }
    } else {
        echo functions::display_error($lng_mail['no_contact_is_chose']);
    }
} else {
    echo'<div class="topmenu"><a href="index.php">' . $lng_mail['my_contacts'] . '</a> | <b>' . $lng_mail['blocklist'] . '</b></div>';
    //Отображаем список заблокированных контактов
    $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_contact` WHERE `user_id` = '" . $user_id . "' AND `ban`='1'"), 0);
    if ($total) {
        if ($total > $kmess) echo '<div class="topmenu">' . functions::display_pagination('index.php?act=ignor&amp;', $start, $total, $kmess) . '</div>';
        $req = mysql_query("SELECT `users`.* FROM `cms_contact`
		    LEFT JOIN `users` ON `cms_contact`.`from_id`=`users`.`id`
		    WHERE `cms_contact`.`user_id`='" . $user_id . "'
		    AND `ban`='1'
		    ORDER BY `cms_contact`.`time` DESC
		    LIMIT $start, $kmess"
        );

        for ($i = 0; ($row = mysql_fetch_assoc($req)) !== FALSE; ++$i) {
            echo $i % 2 ? '<div class="list1">' : '<div class="list2">';
            $subtext = '<a href="index.php?act=write&amp;id=' . $row['id'] . '">' . $lng_mail['correspondence'] . '</a> | <a href="index.php?act=deluser&amp;id=' . $row['id'] . '">' . $lng['delete'] . '</a> | <a href="index.php?act=ignor&amp;id=' . $row['id'] . '&amp;del">' . $lng_mail['enabled'] . '</a>';
            $count_message = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_mail` WHERE ((`user_id`='{$row['id']}' AND `from_id`='$user_id') OR (`user_id`='$user_id' AND `from_id`='{$row['id']}')) AND `delete`!='$user_id' AND `sys`!='1' AND `spam`!='1';"), 0);
            $new_count_message = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_mail` WHERE `cms_mail`.`user_id`='$user_id' AND `cms_mail`.`from_id`='{$row['id']}' AND `read`='0' AND `delete`!='$user_id' AND `sys`!='1' AND `spam`!='1';"), 0);
            $arg = array(
                'header' => '(' . $count_message . ($new_count_message ? '/<span class="red">+' . $new_count_message . '</span>' : '') . ')',
                'sub'    => $subtext
            );
            echo functions::display_user($row, $arg);
            echo '</div>';
        }
    } else {
        echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
    }

    echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
    if ($total > $kmess) {
        echo '<div class="topmenu">' . functions::display_pagination('index.php?act=ignor&amp;', $start, $total, $kmess) . '</div>';
        echo '<p><form action="index.php" method="get">
			<input type="hidden" name="act" value="ignor"/>
			<input type="text" name="page" size="2"/>
			<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
    }
}
echo '<p><a href="../users/profile.php?act=office">' . $lng['personal'] . '</a></p>';