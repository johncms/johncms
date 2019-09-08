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

$textl = _t('Mail');
require_once('../system/head.php');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

echo '<div class="phdr"><b>' . _t('New Messages') . '</b></div>';
$total = $db->query('SELECT COUNT(DISTINCT `user_id`) FROM `cms_mail` WHERE `from_id` = ' . $systemUser->id . ' AND `delete` != ' . $systemUser->id . ' AND `read` = 0')->fetchColumn();

if ($total == 1) {
    //Если все новые сообщения от одного итого же чела показываем сразу переписку
    $res = $db->query("SELECT `user_id` FROM `cms_mail` WHERE `from_id`='" . $systemUser->id . "' AND `read`='0' AND `sys`='0' AND `delete` != " . $systemUser->id)->fetch();
    header('Location: index.php?act=write&id=' . $res['user_id']);
    exit();
}

if ($total) {
    if ($total > $kmess) {
        echo '<div class="topmenu">' . $tools->displayPagination('index.php?act=new&amp;', $start, $total, $kmess) . '</div>';
    }

    //Групируем по контактам
    $query = $db->query("SELECT `users`.* FROM `cms_mail`
		LEFT JOIN `users` ON `cms_mail`.`user_id`=`users`.`id`
		LEFT JOIN `cms_contact` ON `cms_mail`.`user_id`=`cms_contact`.`user_id`
		WHERE `cms_mail`.`from_id`='" . $systemUser->id . "'
		AND `cms_mail`.`read`='0'
		AND `cms_mail`.`delete` != " . $systemUser->id . "
		GROUP BY `cms_mail`.`user_id`
		ORDER BY `cms_contact`.`time` DESC
		LIMIT $start, $kmess"
    );

    for ($i = 0; ($row = $query->fetch()) !== false; ++$i) {
        echo $i % 2 ? '<div class="list1">' : '<div class="list2">';
        $subtext = '<a href="index.php?act=write&amp;id=' . $row['id'] . '">' . _t('Correspondence') . '</a> | <a href="index.php?act=deluser&amp;id=' . $row['id'] . '">' . _t('Delete') . '</a> | <a href="index.php?act=ignor&amp;id=' . $row['id'] . '&amp;add">' . _t('Block Sender') . '</a>';
        $count_message = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE ((`user_id`='{$row['id']}' AND `from_id`='" . $systemUser->id . "') OR (`user_id`='" . $systemUser->id . "' AND `from_id`='{$row['id']}')) AND `delete`!='" . $systemUser->id . "' AND `spam`='0'")->rowCount();
        $new_count_message = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE `cms_mail`.`user_id`='{$row['id']}' AND `cms_mail`.`from_id`='" . $systemUser->id . "' AND `read`='0' AND `delete`!='" . $systemUser->id . "' AND `spam`='0'")->rowCount();
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

echo '<div class="phdr">' . _t('Total') . ': ' . $new_mail . '</div>';

if ($total > $kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('index.php?act=new&amp;', $start, $total, $kmess) . '</div>';
    echo '<p><form action="index.php" method="get">
		<input type="hidden" name="act" value="new"/>
		<input type="text" name="page" size="2"/>
		<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
}

echo '<p><a href="../profile/?act=office">' . _t('Personal') . '</a></p>';
