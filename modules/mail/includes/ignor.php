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
echo '<div class="phdr"><b>' . _t('Blocklist') . '</b></div>';

if (isset($_GET['del'])) {
    if ($id) {
        //Проверяем существование пользователя
        $req = $db->query('SELECT * FROM `users` WHERE `id` = ' . $id);

        if (! $req->rowCount()) {
            echo $view->render(
                'system::app/old_content',
                [
                    'title'   => $textl,
                    'content' => $tools->displayError(_t('User does not exists')),
                ]
            );
            exit;
        }

        //Удаляем из заблокированных
        if (isset($_POST['submit'])) {
            $q = $db->query("SELECT * FROM `cms_contact` WHERE `user_id`='" . $user->id . "' AND `from_id`='" . $id . "' AND `ban`='1'");

            if (! $q->rowCount()) {
                echo '<div class="rmenu">' . _t('User not blocked') . '</div>';
            } else {
                $db->exec("UPDATE `cms_contact` SET `ban`='0' WHERE `user_id`='" . $user->id . "' AND `from_id`='${id}' AND `ban`='1'");
                echo '<div class="rmenu">' . _t('User is unblocked') . '</div>';
            }
        } else {
            echo '<div class="gmenu"><form action="?act=ignor&amp;id=' . $id . '&amp;del" method="post"><div>
			' . _t('You really want to unblock contact?') . '<br />
			<input type="submit" name="submit" value="' . _t('Unblock') . '"/>
			</div></form></div>';
        }
    } else {
        echo $tools->displayError(_t('Contact isn\'t chosen'));
    }
} elseif (isset($_GET['add'])) {
    if ($id) {
        $req = $db->query('SELECT * FROM `users` WHERE `id` = ' . $id);

        if (! $req->rowCount()) {
            echo $view->render(
                'system::app/old_content',
                [
                    'title'   => $textl,
                    'content' => $tools->displayError(_t('User does not exists')),
                ]
            );
            exit;
        }

        $res = $req->fetch();

        //Добавляем в заблокированные
        if (isset($_POST['submit'])) {
            if ($res['rights'] > $user->rights) {
                echo '<div class="rmenu">' . _t('This user can not be blocked') . '</div>';
            } else {
                $q = $db->query(
                    "SELECT * FROM `cms_contact`
				WHERE `user_id`='" . $user->id . "' AND `from_id`='" . $id . "';"
                );

                if (! $q->rowCount()) {
                    $db->query(
                        "INSERT INTO `cms_contact` SET
					`user_id` = '" . $user->id . "',
					`from_id` = '" . $id . "',
					`time` = '" . time() . "',
					`ban`='1'"
                    );
                } else {
                    $db->exec("UPDATE `cms_contact` SET `ban`='1', `friends`='0', `type`='1' WHERE `user_id`='" . $user->id . "' AND `from_id`='${id}'");
                    $db->exec("UPDATE `cms_contact` SET `friends`='0', `type`='1' WHERE `user_id`='${id}' AND `from_id`='" . $user->id . "'");
                }

                echo '<div class="rmenu">' . _t('User is blocked') . '</div>';
            }
        } else {
            echo '<div class="rmenu"><form action="?act=ignor&amp;id=' . $id . '&amp;add" method="post">
			<p>' . _t('You really want to block contact?') . '</p>
			<p><input type="submit" name="submit" value="' . _t('Block') . '"/></p>
			</form></div>';
            echo '<div class="phdr"><a href="' . (isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : './') . '">' . _t('Back') . '</a></div>';
        }
    } else {
        echo $tools->displayError(_t('Contact isn\'t chosen'));
    }
} else {
    echo '<div class="topmenu"><a href="./">' . _t('My Contacts') . '</a> | <b>' . _t('Blocklist') . '</b></div>';

    //Отображаем список заблокированных контактов
    $total = $db->query("SELECT COUNT(*) FROM `cms_contact` WHERE `user_id` = '" . $user->id . "' AND `ban`='1'")->fetchColumn();

    if ($total) {
        if ($total > $user->config->kmess) {
            echo '<div class="topmenu">' . $tools->displayPagination('?act=ignor&amp;', $start, $total, $user->config->kmess) . '</div>';
        }

        $req = $db->query(
            "SELECT `users`.* FROM `cms_contact`
		    LEFT JOIN `users` ON `cms_contact`.`from_id`=`users`.`id`
		    WHERE `cms_contact`.`user_id`='" . $user->id . "'
		    AND `ban`='1'
		    ORDER BY `cms_contact`.`time` DESC
		    LIMIT ${start}, " . $user->config->kmess
        );

        for ($i = 0; ($row = $req->fetch()) !== false; ++$i) {
            echo ($i % 2) ? '<div class="list1">' : '<div class="list2">';
            $subtext = '<a href="?act=write&amp;id=' . $row['id'] . '">' . _t('Correspondence') . '</a> | <a href="?act=deluser&amp;id=' . $row['id'] . '">' .
                _t('Delete') . '</a> | <a href="?act=ignor&amp;id=' . $row['id'] . '&amp;del">' . _t('Unblock') . '</a>';
            $count_message = $db->query(
                "SELECT COUNT(*) FROM `cms_mail`
                WHERE ((`user_id`='{$row['id']}' AND `from_id`='" . $user->id . "') OR (`user_id`='" . $user->id . "' AND `from_id`='{$row['id']}'))
                AND `delete`!='" . $user->id . "' AND `sys`!='1' AND `spam`!='1'"
            )->fetchColumn();
            $new_count_message = $db->query(
                "SELECT COUNT(*) FROM `cms_mail` WHERE `cms_mail`.`user_id`='" . $user->id . "' AND `cms_mail`.`from_id`='{$row['id']}' AND `read`='0' AND `delete`!='" . $user->id . "' AND `sys`!='1' AND `spam`!='1'"
            )->fetchColumn();
            $arg = [
                'header' => '(' . $count_message . ($new_count_message ? '/<span class="red">+' . $new_count_message . '</span>' : '') . ')',
                'sub'    => $subtext,
            ];
            echo $tools->displayUser($row, $arg);
            echo '</div>';
        }
    } else {
        echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
    }

    echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

    if ($total > $user->config->kmess) {
        echo '<div class="topmenu">' . $tools->displayPagination('?act=ignor&amp;', $start, $total, $user->config->kmess) . '</div>';
        echo '<p><form method="get">
			<input type="hidden" name="act" value="ignor"/>
			<input type="text" name="page" size="2"/>
			<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
    }
}

echo '<p><a href="../profile/?act=office">' . _t('Personal') . '</a></p>';
