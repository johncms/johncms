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

echo '<div class="phdr"><h3>' . $lng_mail['deleted_messages'] . '</h3></div>';
if ($id) {
    //Проверяем наличие сообщения
    $stmt = $db->query("SELECT * FROM `cms_mail` WHERE (`user_id`='$user_id' OR `from_id`='$user_id') AND `id` = '$id' AND `delete`!='$user_id' LIMIT 1;");
    if (!$stmt->rowCount()) {
        //Выводим ошибку
        echo functions::display_error($lng_mail['messages_does_not_exist']);
        require_once("../incfiles/end.php");
        exit;
    }
    $res = $stmt->fetch();
    if (isset($_POST['submit'])) { //Если кнопка "Подвердить" нажата
        //Удаляем системное сообщение
        if ($res['sys']) {
            $db->exec("DELETE FROM `cms_mail` WHERE `from_id`='$user_id' AND `id` = '$id' AND `sys`='1' LIMIT 1");
            echo '<div class="gmenu">' . $lng_mail['messages_delete_ok'] . '</div>';
            echo '<div class="bmenu"><a href="index.php?act=systems">' . $lng['back'] . '</a></div>';
        } else {
            //Удаляем непрочитанное сообщение
            if ($res['read'] == 0 && $res['user_id'] == $user_id) {
                //Удаляем файл
                if ($res['file_name'])
                    @unlink('../files/mail/' . $res['file_name']);
                $db->exec("DELETE FROM `cms_mail` WHERE `user_id`='$user_id' AND `id` = '$id' LIMIT 1");
            } else {
                //Удаляем остальные сообщения
                if ($res['delete']) {
                    //Удаляем файл
                    if ($res['file_name'])
                        @unlink('../files/mail/' . $res['file_name']);
                    $db->exec("DELETE FROM `cms_mail` WHERE (`user_id`='$user_id' OR `from_id`='$user_id') AND `id` = '$id' LIMIT 1");
                } else {
                    $db->exec("UPDATE `cms_mail` SET `delete` = '$user_id' WHERE `id` = '$id' LIMIT 1");
                }
            }
            echo '<div class="gmenu">' . $lng_mail['messages_delete_ok'] . '</div>';
            echo '<div class="bmenu"><a href="index.php?act=write&amp;id=' . ($res['user_id'] == $user_id ? $res['from_id'] : $res['user_id']) . '">' . $lng['back'] . '</a></div>';
        }
    } else {
        echo '<div class="gmenu"><form action="index.php?act=delete&amp;id=' . $id . '" method="post"><div>
		' . $lng_mail['really_delete_message'] . '<br />
		<input type="submit" name="submit" value="' . $lng['delete'] . '"/>
		</div></form></div>';
    }
} else {
    echo '<div class="rmenu">' . $lng_mail['not_message_is_chose'] . '</div>';
}
echo '<div class="bmenu"><a href="../users/profile.php?act=office">' . $lng_mail['in_office'] . '</a></div>';