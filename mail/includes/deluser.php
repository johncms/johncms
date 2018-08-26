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

if ($id) {
    //Проверяем наличие контакта в Вашем списке
    $total = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `user_id`='$user_id' AND `from_id`='$id';")->fetchColumn();
    if ($total) {
        if (isset($_POST['submit'])) { //Если кнопка "Удалить" нажата
            $count_message = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE ((`user_id`='$id' AND `from_id`='$user_id') OR (`user_id`='$user_id' AND `from_id`='$id')) AND `delete`!='$user_id';")->fetchColumn();
            if ($count_message) {
                $stmt = $db->query("SELECT `cms_mail`.* FROM `cms_mail` WHERE ((`cms_mail`.`user_id`='$id' AND `cms_mail`.`from_id`='$user_id') OR (`cms_mail`.`user_id`='$user_id' AND `cms_mail`.`from_id`='$id')) AND `cms_mail`.`delete`!='$user_id' LIMIT " . $count_message);
                while ($row = $stmt->fetch()) {
                    //Удаляем сообщения
                    if ($row['delete']) {
                        //Удаляем файлы
                        if ($row['file_name']) {
                            if (file_exists('../files/mail/' . $row['file_name']) !== FALSE) {
                                @unlink('../files/mail/' . $row['file_name']);
                            }
                        }
                        $db->exec("DELETE FROM `cms_mail` WHERE `id`='{$row['id']}' LIMIT 1");
                    } else {
                        if ($row['read'] == 0 && $row['user_id'] == $user_id) {
                            if ($row['file_name']) {
                                if (file_exists('../files/mail/' . $row['file_name']) !== FALSE) {
                                    @unlink('../files/mail/' . $row['file_name']);
                                }
                            }
                            $db->exec("DELETE FROM `cms_mail` WHERE `id`='{$row['id']}' LIMIT 1");
                        } else {
                            $db->exec("UPDATE `cms_mail` SET `delete` = '" . $user_id . "' WHERE `id` = '" . $row['id'] . "' LIMIT 1");
                        }
                    }
                }
            }
            //Удаляем контакт
            $db->exec("DELETE FROM `cms_contact` WHERE `user_id`='$user_id' AND `from_id`='$id' LIMIT 1");
            echo '<div class="gmenu"><p>' . $lng_mail['contact_delete'] . '</p><p><a href="index.php">' . $lng['continue'] . '</a></p></div>';
        } else {
            echo '<div class="phdr"><b>' . $lng['delete'] . '</b></div>
			<div class="rmenu">
			<form action="index.php?act=deluser&amp;id=' . $id . '" method="post">
			<p>' . $lng_mail['really_delete_contact'] . '</p>
			<p><input type="submit" name="submit" value="' . $lng['delete'] . '"/></p>
			</form>
			</div>';
            echo '<div class="phdr"><a href="' . (isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : 'index.php') . '">' . $lng['back'] . '</a></div>';
        }
    } else {
        echo '<div class="rmenu"><p>' . $lng_mail['contact_does_not_exist'] . '</p><p><a href="index.php">' . $lng['continue'] . '</a></p></div>';
    }
} else {
    echo '<div class="rmenu"><p>' . $lng_mail['not_contact_is_chose'] . '</p></div>';
}
