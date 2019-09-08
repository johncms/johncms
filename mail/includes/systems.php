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

$out = '';
$total = 0;
$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

if ($mod == 'clear') {
    if (isset($_POST['clear'])) {
        $count_message = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE `from_id`='" . $systemUser->id . "' AND `sys`='1';")->fetchColumn();

        if ($count_message) {
            $req = $db->query("SELECT `id` FROM `cms_mail` WHERE `from_id`='" . $systemUser->id . "' AND `sys`='1' LIMIT " . $count_message);
            $mass_del = [];

            while ($row = $req->fetch()) {
                $mass_del[] = $row['id'];
            }

            if ($mass_del) {
                $result = implode(',', $mass_del);
                $db->exec("DELETE FROM `cms_mail` WHERE `id` IN (" . $result . ")");
            }
        }
        $out .= '<div class="gmenu">' . _t('Messages are deleted') . '</div>';
    } else {
        $out .= '
		<div class="rmenu">' . _t('Confirm the deletion of messages') . '</div>
		<div class="gmenu">
		<form action="index.php?act=systems&amp;mod=clear" method="post"><div>
		<input type="submit" name="clear" value="' . _t('Delete') . '"/>
		</div></form>
		</div>';
    }
} else {
    $total = $db->query("SELECT COUNT(*) FROM `cms_mail` WHERE `from_id`='" . $systemUser->id . "' AND `sys`='1' AND `delete`!='" . $systemUser->id . "'")->fetchColumn();

    if ($total) {
        function time_parce($var)
        {
            global $tools;

            return $tools->displayDate($var[1]);
        }

        if ($total > $kmess) {
            $out .= '<div class="topmenu">' . $tools->displayPagination('index.php?act=systems&amp;', $start, $total, $kmess) . '</div>';
        }

        $req = $db->query("SELECT * FROM `cms_mail` WHERE `from_id`='" . $systemUser->id . "' AND `sys`='1' AND `delete`!='" . $systemUser->id . "' ORDER BY `time` DESC LIMIT " . $start . "," . $kmess);
        $mass_read = [];

        for ($i = 0; ($row = $req->fetch()) !== false; ++$i) {
            $out .= $i % 2 ? '<div class="list1">' : '<div class="list2">';

            if ($row['read'] == 0 && $row['from_id'] == $systemUser->id) {
                $mass_read[] = $row['id'];
            }

            $post = $row['text'];
            $post = $tools->checkout($post, 1, 1);
            $post = $tools->smilies($post);
            $out .= '<strong>' . $tools->checkout($row['them']) . '</strong> (' . $tools->displayDate($row['time']) . ')<br />';
            $post = preg_replace_callback("/{TIME=(.+?)}/usi", 'time_parce', $post);
            $out .= $post;
            $out .= '<div class="sub"><a href="index.php?act=delete&amp;id=' . $row['id'] . '">' . _t('Delete') . '</a></div>';
            $out .= '</div>';
        }

        //Ставим метку о прочтении
        if ($mass_read) {
            $result = implode(',', $mass_read);
            $db->exec("UPDATE `cms_mail` SET `read`='1' WHERE `from_id`='" . $systemUser->id . "' AND `sys`='1' AND `id` IN (" . $result . ")");
        }
    } else {
        $out .= '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
    }

    $out .= '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

    if ($total > $kmess) {
        $out .= '<div class="topmenu">' . $tools->displayPagination('index.php?act=systems&amp;', $start, $total, $kmess) . '</div>';
        $out .= '<p><form action="index.php" method="get">
			<input type="hidden" name="act" value="systems"/>
			<input type="text" name="page" size="2"/>
			<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
    }
}

$textl = _t('Mail');
require_once('../system/head.php');
echo '<div class="phdr"><b>' . _t('System messages') . '</b></div>';
echo $out;
echo '<p>';

if ($total) {
    echo '<a href="index.php?act=systems&amp;mod=clear">' . _t('Clear messages') . '</a><br>';
}

echo '<a href="../profile/?act=office">' . _t('Personal') . '</a></p>';
