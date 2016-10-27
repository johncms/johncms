<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

$textl = htmlspecialchars($user['name']) . ': ' . _t('IP History');
require('../system/head.php');

// Проверяем права доступа
if (!$rights && $user_id != $user['id']) {
    echo functions::display_error(_t('Access forbidden'));
    require('../incfiles/end.php');
    exit;
}

/** @var Interop\Container\ContainerInterface $container */
$container = App::getContainer();
$config = $container->get('config')['johncms'];

/** @var PDO $db */
$db = $container->get(PDO::class);

// История IP адресов
echo '<div class="phdr"><a href="?user=' . $user['id'] . '"><b>' . _t('Profile') . '</b></a> | ' . _t('IP History') . '</div>';
echo '<div class="user"><p>';
$arg = array(
    'lastvisit' => 1,
    'header' => '<b>ID:' . $user['id'] . '</b>'
);
echo functions::display_user($user, $arg);
echo '</p></div>';

$total = $db->query("SELECT COUNT(*) FROM `cms_users_iphistory` WHERE `user_id` = '" . $user['id'] . "'")->fetchColumn();

if ($total) {
    $req = $db->query("SELECT * FROM `cms_users_iphistory` WHERE `user_id` = '" . $user['id'] . "' ORDER BY `time` DESC LIMIT $start, $kmess");
    $i = 0;

    while ($res = $req->fetch()) {
        echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
        $link = $rights ? '<a href="' . $config['homeurl'] . '/admin/index.php?act=search_ip&amp;mod=history&amp;ip=' . long2ip($res['ip']) . '">' . long2ip($res['ip']) . '</a>' : long2ip($res['ip']);
        echo $link . ' <span class="gray">(' . date("d.m.Y / H:i", $res['time']) . ')</span></div>';
        ++$i;
    }
} else {
    echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
}

echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

if ($total > $kmess) {
    echo '<p>' . functions::display_pagination('?act=ip&amp;user=' . $user['id'] . '&amp;', $start, $total, $kmess) . '</p>';
    echo '<p><form action="?act=ip&amp;user=' . $user['id'] . '" method="post">' .
        '<input type="text" name="page" size="2"/>' .
        '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
        '</form></p>';
}
