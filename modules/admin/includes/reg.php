<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNADM') || die('Error: restricted access');
ob_start(); // Перехват вывода скриптов без шаблона

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 */

echo '<div class="phdr"><a href="./"><b>' . __('Admin Panel') . '</b></a> | ' . __('Registration confirmation') . '</div>';

switch ($mod) {
    case 'approve':
        // Подтверждаем регистрацию выбранного пользователя
        if (! $id) {
            echo $tools->displayError(__('Wrong data'));
            echo $view->render('system::app/old_content', ['content' => ob_get_clean()]);
            exit;
        }

        $db->exec('UPDATE `users` SET `preg` = 1, `regadm` = ' . $db->quote($user->name) . ' WHERE `id` = ' . $id);
        echo '<div class="menu"><p>' . __('Registration is confirmed') . '<br><a href="?act=reg">' . __('Continue') . '</a></p></div>';
        break;

    case 'massapprove':
        // Подтверждение всех регистраций
        $db->exec('UPDATE `users` SET `preg` = 1, `regadm` = ' . $db->quote($user->name) . ' WHERE `preg` = 0');
        echo '<div class="menu"><p>' . __('Registration is confirmed') . '<br><a href="?act=reg">' . __('Continue') . '</a></p></div>';
        break;

    case 'del':
        // Удаляем регистрацию выбранного пользователя
        if (! $id) {
            echo $tools->displayError(__('Wrong data'));
            echo $view->render('system::app/old_content', ['content' => ob_get_clean()]);
            exit;
        }

        $req = $db->query("SELECT `id` FROM `users` WHERE `id` = '${id}' AND `preg` = '0'");

        if ($req->rowCount()) {
            $db->exec("DELETE FROM `users` WHERE `id` = '${id}'");
            $db->exec("DELETE FROM `cms_users_iphistory` WHERE `user_id` = '${id}' LIMIT 1");
        }

        echo '<div class="menu"><p>' . __('User deleted') . '<br><a href="?act=reg">' . __('Continue') . '</a></p></div>';
        break;

    case 'massdel':
        $db->exec("DELETE FROM `users` WHERE `preg` = '0'");
        $db->query('OPTIMIZE TABLE `cms_users_iphistory` , `users`');
        echo '<div class="menu"><p>' . __('All unconfirmed registrations were removed') . '<br><a href="?act=reg">' . __('Continue') . '</a></p></div>';
        break;

    case 'delip':
        // Удаляем все регистрации с заданным адресом IP
        $ip = isset($_GET['ip']) ? (int) ($_GET['ip']) : false;

        if ($ip) {
            $req = $db->query("SELECT `id` FROM `users` WHERE `preg` = '0' AND `ip` = '${ip}'");

            while ($res = $req->fetch()) {
                $db->exec("DELETE FROM `cms_users_iphistory` WHERE `user_id` = '" . $res['id'] . "'");
            }

            $db->exec("DELETE FROM `users` WHERE `preg` = '0' AND `ip` = '${ip}'");
            $db->query('OPTIMIZE TABLE `cms_users_iphistory` , `users`');
            echo '<div class="menu"><p>' . __('All unconfirmed registrations with selected IP were deleted') . '<br>' .
                '<a href="?act=reg">' . __('Continue') . '</a></p></div>';
        } else {
            echo $tools->displayError(__('Wrong data'));
            echo $view->render('system::app/old_content', ['content' => ob_get_clean()]);
            exit;
        }
        break;

    default:
        // Выводим список пользователей, ожидающих подтверждения регистрации
        $total = $db->query("SELECT COUNT(*) FROM `users` WHERE `preg` = '0'")->fetchColumn();

        if ($total > $user->config->kmess) {
            echo '<div class="topmenu">' . $tools->displayPagination('?act=reg&amp;', $start, $total, $user->config->kmess) . '</div>';
        }

        if ($total) {
            $req = $db->query("SELECT * FROM `users` WHERE `preg` = '0' ORDER BY `id` DESC LIMIT " . $start . ',' . $user->config->kmess);
            $i = 0;

            while ($res = $req->fetch()) {
                $link = [
                    '<a href="?act=reg&amp;mod=approve&amp;id=' . $res['id'] . '">' . __('Approve') . '</a>',
                    '<a href="?act=reg&amp;mod=del&amp;id=' . $res['id'] . '">' . __('Delete') . '</a>',
                    '<a href="?act=reg&amp;mod=delip&amp;ip=' . $res['ip'] . '">' . __('Remove IP') . '</a>',
                ];
                echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                echo $tools->displayUser(
                    $res,
                    [
                        'header' => '<b>ID:' . $res['id'] . '</b>',
                        'sub'    => implode(' | ', $link),
                    ]
                );
                echo '</div>';
                ++$i;
            }
        } else {
            echo '<div class="menu"><p>' . __('The list is empty') . '</p></div>';
        }

        echo '<div class="phdr">' . __('Total') . ': ' . $total . '</div>';

        if ($total > $user->config->kmess) {
            echo '<div class="topmenu">' . $tools->displayPagination('?act=reg&amp;', $start, $total, $user->config->kmess) . '</div>' .
                '<p><form action="?act=reg" method="post">' .
                '<input type="text" name="page" size="2"/>' .
                '<input type="submit" value="' . __('To Page') . ' &gt;&gt;"/>' .
                '</form></p>';
        }

        echo '<p>';

        if ($total) {
            echo '<a href="?act=reg&amp;mod=massapprove">' . __('Confirm all') . '</a><br><a href="?act=reg&amp;mod=massdel">' . __('Delete all') . '</a><br>';
        }

        echo '<a href="./">' . __('Admin Panel') . '</a></p>';
}

echo $view->render(
    'system::app/old_content',
    [
        'title'   => __('Admin Panel'),
        'content' => ob_get_clean(),
    ]
);
