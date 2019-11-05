<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNCMS') || die('Error: restricted access');

$textl = _t('Mail');
echo '<div class="phdr"><h3>' . _t('Deleting messages') . '</h3></div>';

if ($id) {
    //Проверяем наличие сообщения
    $req = $db->query("SELECT * FROM `cms_mail` WHERE (`user_id`='" . $user->id . "' OR `from_id`='" . $user->id . "') AND `id` = '${id}' AND `delete`!='" . $user->id . "' LIMIT 1");

    if (! $req->rowCount()) {
        //Выводим ошибку
        echo $view->render('system::app/old_content', [
            'title'   => $textl,
            'content' => $tools->displayError(_t('Message does not exist')),
        ]);
        exit;
    }

    $res = $req->fetch();

    if (isset($_POST['submit'])) { //Если кнопка "Подвердить" нажата
        //Удаляем системное сообщение
        if ($res['sys']) {
            $db->exec("DELETE FROM `cms_mail` WHERE `from_id`='" . $user->id . "' AND `id` = '${id}' AND `sys`='1' LIMIT 1");
            echo '<div class="gmenu">' . _t('Message deleted') . '</div>';
            echo '<div class="bmenu"><a href="?act=systems">' . _t('Back') . '</a></div>';
        } else {
            //Удаляем непрочитанное сообщение
            if ($res['read'] == 0 && $res['user_id'] == $user->id) {
                //Удаляем файл
                if ($res['file_name']) {
                    @unlink(UPLOAD_PATH . 'mail/' . $res['file_name']);
                }

                $db->exec("DELETE FROM `cms_mail` WHERE `user_id`='" . $user->id . "' AND `id` = '${id}' LIMIT 1");
            } else {
                //Удаляем остальные сообщения
                if ($res['delete']) {
                    //Удаляем файл
                    if ($res['file_name']) {
                        @unlink(UPLOAD_PATH . 'mail/' . $res['file_name']);
                    }

                    $db->exec("DELETE FROM `cms_mail` WHERE (`user_id`='" . $user->id . "' OR `from_id`='" . $user->id . "') AND `id` = '${id}' LIMIT 1");
                } else {
                    $db->exec("UPDATE `cms_mail` SET `delete` = '" . $user->id . "' WHERE `id` = '${id}' LIMIT 1");
                }
            }

            echo '<div class="gmenu">' . _t('Message deleted') . '</div>';
            echo '<div class="bmenu"><a href="?act=write&amp;id=' . ($res['user_id'] == $user->id ? $res['from_id'] : $res['user_id']) . '">' . _t('Back') . '</a></div>';
        }
    } else {
        echo '<div class="gmenu"><form action="?act=delete&amp;id=' . $id . '" method="post"><div>
		' . _t('You really want to remove the message?') . '<br />
		<input type="submit" name="submit" value="' . _t('Delete') . '"/>
		</div></form></div>';
    }
} else {
    echo '<div class="rmenu">' . _t('The message for removal isn\'t chosen') . '</div>';
}

echo '<div class="bmenu"><a href="../profile/?act=office">' . _t('Personal') . '</a></div>';
