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

$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';

switch ($mod) {
    case 'amnesty':
        if ($user->rights < 9) {
            echo $tools->displayError(__('Amnesty is available for supervisors only'));
        } else {
            echo '<div class="phdr"><a href="?act=ban_panel"><b>' . __('Ban Panel') . '</b></a> | ' . __('Amnesty') . '</div>';

            if (isset($_POST['submit'])) {
                $term = isset($_POST['term']) && $_POST['term'] == 1 ? 1 : 0;

                if ($term) {
                    // Очищаем таблицу Банов
                    $db->query('TRUNCATE TABLE `cms_ban_users`');
                    echo '<div class="gmenu"><p>' . __('Amnesty has been successful') . '</p></div>';
                } else {
                    // Разбаниваем активные Баны
                    $req = $db->query("SELECT * FROM `cms_ban_users` WHERE `ban_time` > '" . time() . "'");

                    while ($res = $req->fetch()) {
                        $ban_left = $res['ban_time'] - time();

                        if ($ban_left < 2592000) {
                            $amnesty_msg = __('Amnesty');
                            $db->exec("UPDATE `cms_ban_users` SET `ban_time`='" . time() . "', `ban_raz`='--${amnesty_msg}--' WHERE `id` = '" . $res['id'] . "'");
                        }
                    }

                    echo '<div class="gmenu"><p>' . __('All the users with active bans were unbanned (Except for bans &quot;till cancel&quot;)') . '</p></div>';
                }
            } else {
                echo '<form action="?act=ban_panel&amp;mod=amnesty" method="post"><div class="menu"><p>' .
                    '<input type="radio" name="term" value="0" checked="checked" />&#160;' . __('Unban all') . '<br>' .
                    '<input type="radio" name="term" value="1" />&#160;' . __('Clear Ban database') .
                    '</p><p><input type="submit" name="submit" value="' . __('Amnesty') . '" />' .
                    '</p></div></form>' .
                    '<div class="phdr"><small>' . __('&quot;Unban All&quot; - terminating all active bans<br>&quot;Clear Database&quot; - terminates all bans and clears an offenses history') . '</small></div>';
            }

            echo '<p><a href="?act=ban_panel">' . __('Ban Panel') . '</a><br><a href="./">' . __('Admin Panel') . '</a></p>';
        }
        break;

    default:
        // БАН-панель, список нарушителей
        echo '<div class="phdr"><a href="./"><b>' . __('Admin Panel') . '</b></a> | ' . __('Ban Panel') . '</div>';
        echo '<div class="topmenu"><span class="gray">' . __('Sort') . ':</span> ';

        if (isset($_GET['count'])) {
            echo '<a href="?act=ban_panel">' . __('Term') . '</a> | ' . __('Violations') . '</div>';
        } else {
            echo __('Term') . ' | <a href="?act=ban_panel&amp;count">' . __('Violations') . '</a></div>';
        }

        $sort = isset($_GET['count']) ? 'bancount' : 'bantime';
        $total = $db->query('SELECT `user_id` FROM `cms_ban_users` GROUP BY `user_id`')->rowCount();

        $req = $db->query(
            "
          SELECT COUNT(`cms_ban_users`.`user_id`) AS `bancount`, MAX(`cms_ban_users`.`ban_time`) AS `bantime`, `cms_ban_users`.`id` AS `ban_id`, `users`.*
          FROM `cms_ban_users` LEFT JOIN `users` ON `cms_ban_users`.`user_id` = `users`.`id`
          GROUP BY `user_id`
          ORDER BY `${sort}` DESC
          LIMIT " . $start . ',' . $user->config->kmess
        );

        if ($req->rowCount()) {
            while ($res = $req->fetch()) {
                echo '<div class="' . ($res['bantime'] > time() ? 'r' : '') . 'menu">';
                $arg = [
                    'header' => '<br><img src="../images/block.gif" width="16" height="16" align="middle" />&#160;<small><a href="../profile/?act=ban&amp;user=' . $res['id'] . '">' . __('Violations history') . '</a> [' . $res['bancount'] . ']</small>', // phpcs:ignore
                ];
                echo $tools->displayUser($res, $arg);
                echo '</div>';
            }
        } else {
            echo '<div class="menu"><p>' . __('The list is empty') . '</p></div>';
        }

        echo '<div class="phdr">' . __('Total') . ': ' . $total . '</div>';

        if ($total > $user->config->kmess) {
            echo '<div class="topmenu">' . $tools->displayPagination('?act=ban_panel&amp;', $start, $total, $user->config->kmess) . '</div>';
            echo '<p><form action="?act=ban_panel" method="post"><input type="text" name="page" size="2"/><input type="submit" value="' . __('To Page') . ' &gt;&gt;"/></form></p>';
        }

        echo '<p>' . ($user->rights == 9 && $total
                ? '<a href="?act=ban_panel&amp;mod=amnesty">' . __('Amnesty') . '</a><br>'
                : '')
            . '<a href="./">' . __('Admin Panel') . '</a></p>';
}

echo $view->render(
    'system::app/old_content',
    [
        'title' => __('Admin Panel'),
        'content' => ob_get_clean(),
    ]
);
