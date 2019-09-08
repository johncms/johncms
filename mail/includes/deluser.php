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

if ($id) {
    /** @var Psr\Container\ContainerInterface $container */
    $container = App::getContainer();

    /** @var PDO $db */
    $db = $container->get(PDO::class);

    /** @var Johncms\Api\UserInterface $systemUser */
    $systemUser = $container->get(Johncms\Api\UserInterface::class);

    if (isset($_POST['submit'])) {
        $req = $db->query('SELECT * FROM `cms_mail` WHERE ((`user_id` = ' . $id . ' AND `from_id` = ' . $systemUser->id . ') OR (`user_id` = ' . $systemUser->id . ' AND `from_id` = ' . $id . ')) AND `delete` != ' . $systemUser->id);

        while ($row = $req->fetch()) {
            //Удаляем сообщения
            if ($row['delete'] > 0 || ($row['read'] == 0 && $row['user_id'] == $systemUser->id)) {
                //Удаляем файлы
                if (!empty($row['file_name']) && file_exists('../files/mail/' . $row['file_name'])) {
                    @unlink('../files/mail/' . $row['file_name']);
                }

                $db->exec('DELETE FROM `cms_mail` WHERE `id` = ' . $row['id']);
            } else {
                $db->exec('UPDATE `cms_mail` SET `delete` = ' . $systemUser->id . ' WHERE `id` = ' . $row['id']);
            }
        }

        //Удаляем контакт
        $db->exec('DELETE FROM `cms_contact` WHERE `user_id` = ' . $systemUser->id . ' AND `from_id` = ' . $id . ' LIMIT 1');
        echo '<div class="gmenu"><p>' . _t('Contact deleted') . '</p><p><a href="index.php">' . _t('Continue') . '</a></p></div>';
    } else {
        echo '<div class="phdr"><b>' . _t('Delete') . '</b></div>
			<div class="rmenu">
			<form action="index.php?act=deluser&amp;id=' . $id . '" method="post">
			<p>' . _t('When you delete a contact is deleted and all correspondence with him.<br>Are you sure you want to delete?') . '</p>
			<p><input type="submit" name="submit" value="' . _t('Delete') . '"/></p>
			</form>
			</div>';
        echo '<div class="phdr"><a href="' . (isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : 'index.php') . '">' . _t('Back') . '</a></div>';
    }
} else {
    echo '<div class="rmenu"><p>' . _t('Contact for removal isn\'t chosen') . '</p></div>';
}
