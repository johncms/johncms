<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

$textl = _t('Mail');
require_once('../system/head.php');

/** @var Interop\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Tools $tools */
$tools = $container->get('tools');

echo '<div class="phdr"><b>' . _t('New Messages') . '</b></div>';
//TODO: Разобраться с запросом
$total = $db->query("SELECT COUNT(*) FROM (SELECT DISTINCT `user_id` FROM `cms_mail` WHERE `from_id`='$user_id' AND `read`='0' AND `spam`='0') a;")->fetchColumn();

if ($total == 1) {
    //Если все новые сообщения от одного итого же чела показываем сразу переписку
    $max = $db->query("SELECT `user_id`, count(*) FROM `cms_mail` WHERE `from_id`='$user_id' AND `read`='0' AND `spam`='0' GROUP BY `user_id`")->fetchColumn();
    header('Location: index.php?act=write&id=' . $max);
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
		WHERE `cms_mail`.`from_id`='" . $user_id . "' AND `cms_mail`.`read`='0' AND `cms_mail`.`spam`='0' GROUP BY `cms_mail`.`user_id` ORDER BY `cms_contact`.`time` DESC LIMIT " . $start . "," . $kmess
    );

    for ($i = 0; ($row = $query->fetch()) !== false; ++$i) {
        echo $i % 2 ? '<div class="list1">' : '<div class="list2">';
        $subtext = '<a href="index.php?act=write&amp;id=' . $row['id'] . '">' . _t('Correspondence') . '</a> | <a href="index.php?act=deluser&amp;id=' . $row['id'] . '">' . _t('Delete') . '</a> | <a href="index.php?act=ignor&amp;id=' . $row['id'] . '&amp;add">' . _t('Block User') . '</a>';
        $count_message = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE ((`user_id`='{$row['id']}' AND `from_id`='$user_id') OR (`user_id`='$user_id' AND `from_id`='{$row['id']}')) AND `delete`!='$user_id' AND `spam`='0';")->rowCount();
        $new_count_message = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE `cms_mail`.`user_id`='{$row['id']}' AND `cms_mail`.`from_id`='$user_id' AND `read`='0' AND `delete`!='$user_id' AND `spam`='0';")->rowCount();
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
