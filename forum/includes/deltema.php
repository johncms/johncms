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

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

if ($systemUser->rights == 3 || $systemUser->rights >= 6) {
    if (!$id) {
        require('../system/head.php');
        echo $tools->displayError(_t('Wrong data'));
        require('../system/end.php');
        exit;
    }

    // Проверяем, существует ли тема
    $req = $db->query("SELECT * FROM `forum` WHERE `id` = '$id' AND `type` = 't'");

    if (!$req->rowCount()) {
        require('../system/head.php');
        echo $tools->displayError(_t('Topic has been deleted or does not exists'));
        require('../system/end.php');
        exit;
    }

    $res = $req->fetch();

    if (isset($_POST['submit'])) {
        $del = isset($_POST['del']) ? intval($_POST['del']) : null;

        if ($del == 2 && $systemUser->rights == 9) {
            // Удаляем топик
            $req1 = $db->query("SELECT * FROM `cms_forum_files` WHERE `topic` = '$id'");

            if ($req1->rowCount()) {
                while ($res1 = $req1->fetch()) {
                    unlink('../files/forum/attach/' . $res1['filename']);
                }

                $db->exec("DELETE FROM `cms_forum_files` WHERE `topic` = '$id'");
                $db->query("OPTIMIZE TABLE `cms_forum_files`");
            }

            $db->exec("DELETE FROM `forum` WHERE `refid` = '$id'");
            $db->exec("DELETE FROM `forum` WHERE `id`='$id'");
        } elseif ($del = 1) {
            // Скрываем топик
            $db->exec("UPDATE `forum` SET `close` = '1', `close_who` = '" . $systemUser->name . "' WHERE `id` = '$id'");
            $db->exec("UPDATE `cms_forum_files` SET `del` = '1' WHERE `topic` = '$id'");
        }
        header('Location: index.php?id=' . $res['refid']);
    } else {
        // Меню выбора режима удаления темы
        require('../system/head.php');
        echo '<div class="phdr"><a href="index.php?id=' . $id . '"><b>' . _t('Forum') . '</b></a> | ' . _t('Delete Topic') . '</div>' .
            '<div class="rmenu"><form method="post" action="index.php?act=deltema&amp;id=' . $id . '">' .
            '<p><h3>' . _t('Do you really want to delete?') . '</h3>' .
            '<input type="radio" value="1" name="del" checked="checked"/>&#160;' . _t('Hide') . '<br />' .
            ($systemUser->rights == 9 ? '<input type="radio" value="2" name="del" />&#160;' . _t('Delete') . '</p>' : '') .
            '<p><input type="submit" name="submit" value="' . _t('Perform') . '" /></p>' .
            '<p><a href="index.php?id=' . $id . '">' . _t('Cancel') . '</a>' .
            '</p></form></div>' .
            '<div class="phdr">&#160;</div>';
    }
}
