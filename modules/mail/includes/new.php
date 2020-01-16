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

$textl = __('Mail');
echo '<div class="phdr"><b>' . __('New Messages') . '</b></div>';
$total = $db->query('SELECT COUNT(DISTINCT `user_id`) FROM `cms_mail` WHERE `from_id` = ' . $user->id . ' AND `delete` != ' . $user->id . ' AND `read` = 0')->fetchColumn();

if ($total == 1) {
    //Если все новые сообщения от одного итого же чела показываем сразу переписку
    $res = $db->query("SELECT `user_id` FROM `cms_mail` WHERE `from_id`='" . $user->id . "' AND `read`='0' AND `sys`='0' AND `delete` != " . $user->id)->fetch();
    header('Location: ?act=write&id=' . $res['user_id']);
    exit();
}

if ($total) {
    if ($total > $user->config->kmess) {
        echo '<div class="topmenu">' . $tools->displayPagination('?act=new&amp;', $start, $total, $user->config->kmess) . '</div>';
    }

    //Групируем по контактам
    $query = $db->query("SELECT `users`.* FROM `cms_mail`
		LEFT JOIN `users` ON `cms_mail`.`user_id`=`users`.`id`
		LEFT JOIN `cms_contact` ON `cms_mail`.`user_id`=`cms_contact`.`user_id`
		WHERE `cms_mail`.`from_id`='" . $user->id . "'
		AND `cms_mail`.`read`='0'
		AND `cms_mail`.`delete` != " . $user->id . "
		GROUP BY `cms_mail`.`user_id`
		ORDER BY `cms_contact`.`time` DESC
		LIMIT ${start}, " . $user->config->kmess);

    for ($i = 0; ($row = $query->fetch()) !== false; ++$i) {
        echo $i % 2 ? '<div class="list1">' : '<div class="list2">';
        $subtext = '<a href="?act=write&amp;id=' . $row['id'] . '">' . __('Correspondence') . '</a> | <a href="?act=deluser&amp;id=' . $row['id'] . '">' . __('Delete') . '</a> | <a href="?act=ignor&amp;id=' . $row['id'] . '&amp;add">' . __('Block Sender') . '</a>'; // phpcs:ignore
        $count_message = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE ((`user_id`='{$row['id']}' AND `from_id`='" . $user->id . "') OR (`user_id`='" . $user->id . "' AND `from_id`='{$row['id']}')) AND `delete`!='" . $user->id . "' AND `spam`='0'")->rowCount(); // phpcs:ignore
        $new_count_message = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE `cms_mail`.`user_id`='{$row['id']}' AND `cms_mail`.`from_id`='" . $user->id . "' AND `read`='0' AND `delete`!='" . $user->id . "' AND `spam`='0'")->rowCount();
        $arg = [
            'header' => '(' . $count_message . ($new_count_message ? '/<span class="red">+' . $new_count_message . '</span>' : '') . ')',
            'sub'    => $subtext,
        ];
        echo $tools->displayUser($row, $arg);
        echo '</div>';
    }
} else {
    echo '<div class="menu"><p>' . __('The list is empty') . '</p></div>';
}

echo '<div class="phdr">' . __('Total') . ': ' . $new_mail . '</div>';

if ($total > $user->config->kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('?act=new&amp;', $start, $total, $user->config->kmess) . '</div>';
    echo '<p><form method="get">
		<input type="hidden" name="act" value="new"/>
		<input type="text" name="page" size="2"/>
		<input type="submit" value="' . __('To Page') . ' &gt;&gt;"/></form></p>';
}

echo '<p><a href="../profile/?act=office">' . __('Personal') . '</a></p>';
