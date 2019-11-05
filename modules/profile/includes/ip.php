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

$textl = htmlspecialchars($foundUser['name']) . ': ' . _t('IP History');

// Проверяем права доступа
if (! $user->rights && $user->id != $foundUser['id']) {
    echo $view->render('system::app/old_content', [
        'title'   => _t('Administration'),
        'content' => $tools->displayError(_t('Access forbidden')),
    ]);
    exit;
}

// История IP адресов
echo '<div class="phdr"><a href="?user=' . $foundUser['id'] . '"><b>' . _t('Profile') . '</b></a> | ' . _t('IP History') . '</div>';
echo '<div class="user"><p>';
$arg = [
    'lastvisit' => 1,
    'header' => '<b>ID:' . $foundUser['id'] . '</b>',
];
echo $tools->displayUser($foundUser, $arg);
echo '</p></div>';

$total = $db->query("SELECT COUNT(*) FROM `cms_users_iphistory` WHERE `user_id` = '" . $foundUser['id'] . "'")->fetchColumn();

if ($total) {
    $req = $db->query("SELECT * FROM `cms_users_iphistory` WHERE `user_id` = '" . $foundUser['id'] . "' ORDER BY `time` DESC LIMIT ${start}, ${kmess}");
    $i = 0;

    while ($res = $req->fetch()) {
        echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
        $link = $user->rights ? '<a href="' . $config['homeurl'] . '/admin/?act=search_ip&amp;mod=history&amp;ip=' . long2ip($res['ip']) . '">' . long2ip($res['ip']) . '</a>' : long2ip($res['ip']);
        echo $link . ' <span class="gray">(' . date('d.m.Y / H:i', $res['time']) . ')</span></div>';
        ++$i;
    }
} else {
    echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
}

echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

if ($total > $kmess) {
    echo '<p>' . $tools->displayPagination('?act=ip&amp;user=' . $foundUser['id'] . '&amp;', $start, $total, $kmess) . '</p>';
    echo '<p><form action="?act=ip&amp;user=' . $foundUser['id'] . '" method="post">' .
        '<input type="text" name="page" size="2"/>' .
        '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
        '</form></p>';
}
