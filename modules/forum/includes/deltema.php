<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $user */
$user = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

if ($user->rights == 3 || $user->rights >= 6) {
    if (! $id) {
        require 'system/head.php';
        echo $tools->displayError(_t('Wrong data'));
        require 'system/end.php';
        exit;
    }

    // Проверяем, существует ли тема
    $req = $db->query("SELECT * FROM `forum_topic` WHERE `id` = '${id}'");

    if (! $req->rowCount()) {
        require 'system/head.php';
        echo $tools->displayError(_t('Topic has been deleted or does not exists'));
        require 'system/end.php';
        exit;
    }

    $res = $req->fetch();

    if (isset($_POST['submit'])) {
        $del = isset($_POST['del']) ? (int) ($_POST['del']) : null;

        if ($del == 2 && $user->rights == 9) {
            // Удаляем топик
            $req1 = $db->query("SELECT * FROM `cms_forum_files` WHERE `topic` = '${id}'");

            if ($req1->rowCount()) {
                while ($res1 = $req1->fetch()) {
                    unlink(UPLOAD_PATH . 'forum/attach/' . $res1['filename']);
                }

                $db->exec("DELETE FROM `cms_forum_files` WHERE `topic` = '${id}'");
                $db->query('OPTIMIZE TABLE `cms_forum_files`');
            }

            $db->exec("DELETE FROM `forum_messages` WHERE `topic_id` = '${id}'");
            $db->exec("DELETE FROM `forum_topic` WHERE `id`='${id}'");
        } elseif ($del = 1) {
            // Скрываем топик
            $db->exec("UPDATE `forum_topic` SET `deleted` = '1', `deleted_by` = '" . $user->name . "' WHERE `id` = '${id}'");
            $db->exec("UPDATE `cms_forum_files` SET `del` = '1' WHERE `topic` = '${id}'");
        }
        header('Location: ?type=topics&id=' . $res['section_id']);
    } else {
        // Меню выбора режима удаления темы
        require 'system/head.php';
        echo '<div class="phdr"><a href="?type=topic&id=' . $id . '"><b>' . _t('Forum') . '</b></a> | ' . _t('Delete Topic') . '</div>' .
            '<div class="rmenu"><form method="post" action="?act=deltema&amp;id=' . $id . '">' .
            '<p><h3>' . _t('Do you really want to delete?') . '</h3>' .
            '<input type="radio" value="1" name="del" checked="checked"/>&#160;' . _t('Hide') . '<br />' .
            ($user->rights == 9 ? '<input type="radio" value="2" name="del" />&#160;' . _t('Delete') . '</p>' : '') .
            '<p><input type="submit" name="submit" value="' . _t('Perform') . '" /></p>' .
            '<p><a href="?type=topic&id=' . $id . '">' . _t('Cancel') . '</a>' .
            '</p></form></div>' .
            '<div class="phdr">&#160;</div>';
    }
}
