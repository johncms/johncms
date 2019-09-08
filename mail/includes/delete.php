<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

$headmod = 'mail';
$textl = _t('Mail');
require_once('../system/head.php');

echo '<div class="phdr"><h3>' . _t('Deleting messages') . '</h3></div>';

if ($id) {
    /** @var Psr\Container\ContainerInterface $container */
    $container = App::getContainer();

    /** @var PDO $db */
    $db = $container->get(PDO::class);

    /** @var Johncms\Api\UserInterface $systemUser */
    $systemUser = $container->get(Johncms\Api\UserInterface::class);

    /** @var Johncms\Api\ToolsInterface $tools */
    $tools = $container->get(Johncms\Api\ToolsInterface::class);

    //Проверяем наличие сообщения
    $req = $db->query("SELECT * FROM `cms_mail` WHERE (`user_id`='" . $systemUser->id . "' OR `from_id`='" . $systemUser->id . "') AND `id` = '$id' AND `delete`!='" . $systemUser->id . "' LIMIT 1");

    if (!$req->rowCount()) {
        //Выводим ошибку
        echo $tools->displayError(_t('Message does not exist'));
        require_once("../system/end.php");
        exit;
    }

    $res = $req->fetch();

    if (isset($_POST['submit'])) { //Если кнопка "Подвердить" нажата
        //Удаляем системное сообщение
        if ($res['sys']) {
            $db->exec("DELETE FROM `cms_mail` WHERE `from_id`='" . $systemUser->id . "' AND `id` = '$id' AND `sys`='1' LIMIT 1");
            echo '<div class="gmenu">' . _t('Message deleted') . '</div>';
            echo '<div class="bmenu"><a href="index.php?act=systems">' . _t('Back') . '</a></div>';
        } else {
            //Удаляем непрочитанное сообщение
            if ($res['read'] == 0 && $res['user_id'] == $systemUser->id) {

                //Удаляем файл
                if ($res['file_name']) {
                    @unlink('../files/mail/' . $res['file_name']);
                }

                $db->exec("DELETE FROM `cms_mail` WHERE `user_id`='" . $systemUser->id . "' AND `id` = '$id' LIMIT 1");
            } else {
                //Удаляем остальные сообщения
                if ($res['delete']) {

                    //Удаляем файл
                    if ($res['file_name']) {
                        @unlink('../files/mail/' . $res['file_name']);
                    }

                    $db->exec("DELETE FROM `cms_mail` WHERE (`user_id`='" . $systemUser->id . "' OR `from_id`='" . $systemUser->id . "') AND `id` = '$id' LIMIT 1");
                } else {
                    $db->exec("UPDATE `cms_mail` SET `delete` = '" . $systemUser->id . "' WHERE `id` = '$id' LIMIT 1");
                }
            }

            echo '<div class="gmenu">' . _t('Message deleted') . '</div>';
            echo '<div class="bmenu"><a href="index.php?act=write&amp;id=' . ($res['user_id'] == $systemUser->id ? $res['from_id'] : $res['user_id']) . '">' . _t('Back') . '</a></div>';
        }
    } else {
        echo '<div class="gmenu"><form action="index.php?act=delete&amp;id=' . $id . '" method="post"><div>
		' . _t('You really want to remove the message?') . '<br />
		<input type="submit" name="submit" value="' . _t('Delete') . '"/>
		</div></form></div>';
    }
} else {
    echo '<div class="rmenu">' . _t('The message for removal isn\'t chosen') . '</div>';
}

echo '<div class="bmenu"><a href="../profile/?act=office">' . _t('Personal') . '</a></div>';
