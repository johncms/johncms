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

defined('_IN_JOHNADM') or die('Error: restricted access');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

// Проверяем права доступа
if ($systemUser->rights < 6) {
    header('Location: http://johncms.com/?err');
    exit;
}

echo '<div class="phdr"><a href="index.php"><b>' . _t('Admin Panel') . '</b></a> | ' . _t('Registration confirmation') . '</div>';

switch ($mod) {
    case 'approve':
        // Подтверждаем регистрацию выбранного пользователя
        if (!$id) {
            echo $tools->displayError(_t('Wrong data'));
            require('../system/end.php');
            exit;
        }

        $db->exec('UPDATE `users` SET `preg` = 1, `regadm` = ' . $db->quote($systemUser->name) . ' WHERE `id` = ' . $id);
        echo '<div class="menu"><p>' . _t('Registration is confirmed') . '<br><a href="index.php?act=reg">' . _t('Continue') . '</a></p></div>';
        break;

    case 'massapprove':
        // Подтверждение всех регистраций
        $db->exec('UPDATE `users` SET `preg` = 1, `regadm` = ' . $db->quote($systemUser->name) . ' WHERE `preg` = 0');
        echo '<div class="menu"><p>' . _t('Registration is confirmed') . '<br><a href="index.php?act=reg">' . _t('Continue') . '</a></p></div>';
        break;

    case 'del':
        // Удаляем регистрацию выбранного пользователя
        if (!$id) {
            echo $tools->displayError(_t('Wrong data'));
            require('../system/end.php');
            exit;
        }

        $req = $db->query("SELECT `id` FROM `users` WHERE `id` = '$id' AND `preg` = '0'");

        if ($req->rowCount()) {
            $db->exec("DELETE FROM `users` WHERE `id` = '$id'");
            $db->exec("DELETE FROM `cms_users_iphistory` WHERE `user_id` = '$id' LIMIT 1");
        }

        echo '<div class="menu"><p>' . _t('User deleted') . '<br><a href="index.php?act=reg">' . _t('Continue') . '</a></p></div>';
        break;

    case 'massdel':
        $db->exec("DELETE FROM `users` WHERE `preg` = '0'");
        $db->query("OPTIMIZE TABLE `cms_users_iphistory` , `users`");
        echo '<div class="menu"><p>' . _t('All unconfirmed registrations were removed') . '<br><a href="index.php?act=reg">' . _t('Continue') . '</a></p></div>';
        break;

    case 'delip':
        // Удаляем все регистрации с заданным адресом IP
        $ip = isset($_GET['ip']) ? intval($_GET['ip']) : false;

        if ($ip) {
            $req = $db->query("SELECT `id` FROM `users` WHERE `preg` = '0' AND `ip` = '$ip'");

            while ($res = $req->fetch()) {
                $db->exec("DELETE FROM `cms_users_iphistory` WHERE `user_id` = '" . $res['id'] . "'");
            }

            $db->exec("DELETE FROM `users` WHERE `preg` = '0' AND `ip` = '$ip'");
            $db->query("OPTIMIZE TABLE `cms_users_iphistory` , `users`");
            echo '<div class="menu"><p>' . _t('All unconfirmed registrations with selected IP were deleted') . '<br>' .
                '<a href="index.php?act=reg">' . _t('Continue') . '</a></p></div>';
        } else {
            echo $tools->displayError(_t('Wrong data'));
            require('../system/end.php');
            exit;
        }
        break;

    default:
        // Выводим список пользователей, ожидающих подтверждения регистрации
        $total = $db->query("SELECT COUNT(*) FROM `users` WHERE `preg` = '0'")->fetchColumn();

        if ($total > $kmess) {
            echo '<div class="topmenu">' . $tools->displayPagination('index.php?act=reg&amp;', $start, $total, $kmess) . '</div>';
        }

        if ($total) {
            $req = $db->query("SELECT * FROM `users` WHERE `preg` = '0' ORDER BY `id` DESC LIMIT $start,$kmess");
            $i = 0;

            while ($res = $req->fetch()) {
                $link = [
                    '<a href="index.php?act=reg&amp;mod=approve&amp;id=' . $res['id'] . '">' . _t('Approve') . '</a>',
                    '<a href="index.php?act=reg&amp;mod=del&amp;id=' . $res['id'] . '">' . _t('Delete') . '</a>',
                    '<a href="index.php?act=reg&amp;mod=delip&amp;ip=' . $res['ip'] . '">' . _t('Remove IP') . '</a>',
                ];
                echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                echo $tools->displayUser($res, [
                    'header' => '<b>ID:' . $res['id'] . '</b>',
                    'sub'    => implode(' | ', $link),
                ]);
                echo '</div>';
                ++$i;
            }
        } else {
            echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
        }

        echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

        if ($total > $kmess) {
            echo '<div class="topmenu">' . $tools->displayPagination('index.php?act=reg&amp;', $start, $total, $kmess) . '</div>' .
                '<p><form action="index.php?act=reg" method="post">' .
                '<input type="text" name="page" size="2"/>' .
                '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
                '</form></p>';
        }

        echo '<p>';

        if ($total) {
            echo '<a href="index.php?act=reg&amp;mod=massapprove">' . _t('Confirm all') . '</a><br><a href="index.php?act=reg&amp;mod=massdel">' . _t('Delete all') . '</a><br>';
        }

        echo '<a href="index.php">' . _t('Admin Panel') . '</a></p>';
}
