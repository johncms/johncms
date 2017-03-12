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

$textl = _t('Forum') . ' | ' . _t('Unread');
$headmod = 'forumnew';
require('../system/head.php');
unset($_SESSION['fsort_id']);
unset($_SESSION['fsort_users']);

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

if (empty($_SESSION['uid'])) {
    if (isset($_GET['newup'])) {
        $_SESSION['uppost'] = 1;
    }
    if (isset($_GET['newdown'])) {
        $_SESSION['uppost'] = 0;
    }
}

if ($systemUser->isValid()) {
    switch ($do) {
        case 'reset':
            // Отмечаем все темы как прочитанные
            $db->exec("INSERT INTO `cms_forum_rdm` (`topic_id`, `user_id`, `time`)
            SELECT `forum`.`id`, '" . $systemUser->id . "', '" . time() . "'
            FROM `forum` LEFT JOIN `cms_forum_rdm` ON `forum`.`id` = `cms_forum_rdm`.`topic_id` AND `cms_forum_rdm`.`user_id` = '" . $systemUser->id . "'
            WHERE `forum`.`type`='t'
            AND `cms_forum_rdm`.`topic_id` IS NULL");

            $ids = $db->query("SELECT `forum`.`id`
            FROM `forum` LEFT JOIN `cms_forum_rdm` ON `forum`.`id` = `cms_forum_rdm`.`topic_id` AND `cms_forum_rdm`.`user_id` = '" . $systemUser->id . "'
            WHERE `forum`.`type`='t'
            AND `forum`.`time` > `cms_forum_rdm`.`time`")->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($ids)) {
                $db->exec("UPDATE `cms_forum_rdm` SET
                    `time` = '" . time() . "'
                    WHERE `topic_id` IN (" . implode(',', $ids) . ") AND `user_id` = '" . $systemUser->id . "'
                ");
            }

            echo '<div class="menu"><p>' . _t('All topics marked as read') . '<br /><a href="index.php">' . _t('Forum') . '</a></p></div>';
            break;

        case 'period':
            // Показ новых тем за выбранный период
            $vr = isset($_REQUEST['vr']) ? abs(intval($_REQUEST['vr'])) : 24;
            $vr1 = time() - $vr * 3600;

            if ($systemUser->rights == 9) {
                $req = $db->query("SELECT COUNT(*) FROM `forum` WHERE `type`='t' AND `time` > '$vr1'");
            } else {
                $req = $db->query("SELECT COUNT(*) FROM `forum` WHERE `type`='t' AND `time` > '$vr1' AND `close` != '1'");
            }

            $count = $req->fetchColumn();
            echo '<div class="phdr"><a href="index.php"><b>' . _t('Forum') . '</b></a> | ' . sprintf(_t('All for period %d hours'), $vr) . '</div>';

            // Форма выбора периода времени
            echo '<div class="topmenu"><form action="index.php?act=new&amp;do=period" method="post">' .
                '<input type="text" maxlength="3" name="vr" value="' . $vr . '" size="3"/>' .
                '<input type="submit" name="submit" value="' . _t('Show period') . '"/>' .
                '</form></div>';

            if ($count > $kmess) {
                echo '<div class="topmenu">' . $tools->displayPagination('index.php?act=new&amp;do=period&amp;vr=' . $vr . '&amp;', $start, $count, $kmess) . '</div>';
            }

            if ($count) {
                if ($systemUser->rights == 9) {
                    $req = $db->query("SELECT * FROM `forum` WHERE `type`='t' AND `time` > '" . $vr1 . "' ORDER BY `time` DESC LIMIT " . $start . "," . $kmess);
                } else {
                    $req = $db->query("SELECT * FROM `forum` WHERE `type`='t' AND `time` > '" . $vr1 . "' AND `close` != '1' ORDER BY `time` DESC LIMIT " . $start . "," . $kmess);
                }

                for ($i = 0; $res = $req->fetch(); ++$i) {
                    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                    $razd = $db->query("SELECT `id`, `refid`, `text` FROM `forum` WHERE `type`='r' AND `id`='" . $res['refid'] . "'")->fetch();
                    $frm = $db->query("SELECT `text` FROM `forum` WHERE `type`='f' AND `id`='" . $razd['refid'] . "'")->fetch();
                    $colmes = $db->query("SELECT * FROM `forum` WHERE `refid` = '" . $res['id'] . "' AND `type` = 'm'" . ($systemUser->rights >= 7 ? '' : " AND `close` != '1'") . " ORDER BY `time` DESC");
                    $colmes1 = $colmes->rowCount();
                    $cpg = ceil($colmes1 / $kmess);
                    $nick = $colmes->fetch();

                    if ($res['edit']) {
                        echo $tools->image('tz.gif');
                    } elseif ($res['close']) {
                        echo $tools->image('dl.gif');
                    } else {
                        echo $tools->image('np.gif');
                    }

                    if ($res['realid'] == 1) {
                        echo $tools->image('rate.gif');
                    }

                    echo '&#160;<a href="index.php?id=' . $res['id'] . ($cpg > 1 && $set_forum['upfp'] && $set_forum['postclip'] ? '&amp;clip' : '') . ($set_forum['upfp'] && $cpg > 1 ? '&amp;page=' . $cpg : '') . '">' . (empty($res['text']) ? '-----' : $res['text']) .
                        '</a>&#160;[' . $colmes1 . ']';
                    if ($cpg > 1) {
                        echo '<a href="index.php?id=' . $res['id'] . (!$set_forum['upfp'] && $set_forum['postclip'] ? '&amp;clip' : '') . ($set_forum['upfp'] ? '' : '&amp;page=' . $cpg) . '">&#160;&gt;&gt;</a>';
                    }

                    echo '<br /><div class="sub"><a href="index.php?id=' . $razd['id'] . '">' . $frm['text'] . '&#160;/&#160;' . $razd['text'] . '</a><br />';
                    echo $res['from'];

                    if ($colmes1 > 1) {
                        echo '&#160;/&#160;' . $nick['from'];
                    }

                    echo ' <span class="gray">' . $tools->displayDate($nick['time']) . '</span>';
                    echo '</div></div>';
                }
            } else {
                echo '<div class="menu"><p>' . _t('There is nothing new in this forum for selected period') . '</p></div>';
            }

            echo '<div class="phdr">' . _t('Total') . ': ' . $count . '</div>';

            if ($count > $kmess) {
                echo '<div class="topmenu">' . $tools->displayPagination('index.php?act=new&amp;do=period&amp;vr=' . $vr . '&amp;', $start, $count, $kmess) . '</div>' .
                    '<p><form action="index.php?act=new&amp;do=period&amp;vr=' . $vr . '" method="post">
                    <input type="text" name="page" size="2"/>
                    <input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
            }
            break;

        default:
            // Вывод непрочитанных тем (для зарегистрированных)
            $total = $container->get('counters')->forumNew();
            echo '<div class="phdr"><a href="index.php"><b>' . _t('Forum') . '</b></a> | ' . _t('Unread') . '</div>';

            if ($total > $kmess) {
                echo '<div class="topmenu">' . $tools->displayPagination('index.php?act=new&amp;', $start, $total, $kmess) . '</div>';
            }

            if ($total > 0) {
                $req = $db->query("SELECT * FROM `forum`
                LEFT JOIN `cms_forum_rdm` ON `forum`.`id` = `cms_forum_rdm`.`topic_id` AND `cms_forum_rdm`.`user_id` = '" . $systemUser->id . "'
                WHERE `forum`.`type`='t'" . ($systemUser->rights >= 7 ? "" : " AND `forum`.`close` != '1'") . "
                AND (`cms_forum_rdm`.`topic_id` Is Null
                OR `forum`.`time` > `cms_forum_rdm`.`time`)
                ORDER BY `forum`.`time` DESC
                LIMIT $start, $kmess");

                for ($i = 0; $res = $req->fetch(); ++$i) {
                    if ($res['close']) {
                        echo '<div class="rmenu">';
                    } else {
                        echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                    }

                    $razd = $db->query("SELECT `id`, `refid`, `text` FROM `forum` WHERE `type` = 'r' AND `id` = '" . $res['refid'] . "' LIMIT 1")->fetch();
                    $frm = $db->query("SELECT `id`, `text` FROM `forum` WHERE `type`='f' AND `id` = '" . $razd['refid'] . "' LIMIT 1")->fetch();
                    $colmes = $db->query("SELECT `from`, `time` FROM `forum` WHERE `refid` = '" . $res['id'] . "' AND `type` = 'm'" . ($systemUser->rights >= 7 ? '' : " AND `close` != '1'") . " ORDER BY `time` DESC");
                    $colmes1 = $colmes->rowCount();
                    $cpg = ceil($colmes1 / $kmess);
                    $nick = $colmes->fetch();

                    // Значки
                    $icons = [
                        (isset($np) ? (!$res['vip'] ? $tools->image('op.gif') : '') : $tools->image('np.gif')),
                        ($res['vip'] ? $tools->image('pt.gif') : ''),
                        ($res['realid'] ? $tools->image('rate.gif') : ''),
                        ($res['edit'] ? $tools->image('tz.gif') : ''),
                    ];
                    echo implode('', array_filter($icons));
                    echo '<a href="index.php?id=' . $res['id'] . ($cpg > 1 && $set_forum['upfp'] && $set_forum['postclip'] ? '&amp;clip' : '') . ($set_forum['upfp'] && $cpg > 1 ? '&amp;page=' . $cpg : '') . '">' . (empty($res['text']) ? '-----' : $res['text']) .
                        '</a>&#160;[' . $colmes1 . ']';

                    if ($cpg > 1) {
                        echo '&#160;<a href="index.php?id=' . $res['id'] . (!$set_forum['upfp'] && $set_forum['postclip'] ? '&amp;clip' : '') . ($set_forum['upfp'] ? '' : '&amp;page=' . $cpg) . '">&gt;&gt;</a>';
                    }

                    echo '<div class="sub">' . $res['from'] . ($colmes1 > 1 ? '&#160;/&#160;' . $nick['from'] : '') .
                        ' <span class="gray">(' . $tools->displayDate($nick['time']) . ')</span><br />' .
                        '<a href="index.php?id=' . $frm['id'] . '">' . $frm['text'] . '</a>&#160;/&#160;<a href="index.php?id=' . $razd['id'] . '">' . $razd['text'] . '</a>' .
                        '</div></div>';
                }
            } else {
                echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
            }

            echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

            if ($total > $kmess) {
                echo '<div class="topmenu">' . $tools->displayPagination('index.php?act=new&amp;', $start, $total, $kmess) . '</div>' .
                    '<p><form action="index.php" method="get">' .
                    '<input type="hidden" name="act" value="new"/>' .
                    '<input type="text" name="page" size="2"/>' .
                    '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
                    '</form></p>';
            }

            if ($total) {
                echo '<p><a href="index.php?act=new&amp;do=reset">' . _t('Mark as read') . '</a></p>';
            }

    }
} else {
    // Вывод 10 последних тем (для незарегистрированных)
    echo '<div class="phdr"><a href="index.php"><b>' . _t('Forum') . '</b></a> | ' . _t('Last 10') . '</div>';
    $req = $db->query("SELECT * FROM `forum` WHERE `type` = 't' AND `close` != '1' ORDER BY `time` DESC LIMIT 10");

    if ($req->rowCount()) {
        for ($i = 0; $res = $req->fetch(); ++$i) {
            $razd = $db->query("SELECT `id`, `refid`, `text` FROM `forum` WHERE type='r' AND id='" . $res['refid'] . "' LIMIT 1")->fetch();
            $frm = $db->query("SELECT `id`, `refid`, `text` FROM `forum` WHERE type='f' AND id='" . $razd['refid'] . "' LIMIT 1")->fetch();
            $nikuser = $db->query("SELECT `from`, `time` FROM `forum` WHERE `type` = 'm' AND `close` != '1' AND `refid` = '" . $res['id'] . "'ORDER BY `time` DESC");
            $colmes1 = $nikuser->rowCount();
            $cpg = ceil($colmes1 / $kmess);
            $nam = $nikuser->fetch();
            echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
            // Значки
            $icons = [
                ($res['vip'] ? $tools->image('pt.gif') : ''),
                ($res['realid'] ? $tools->image('rate.gif') : ''),
                ($res['edit'] ? $tools->image('tz.gif') : ''),
            ];
            echo implode('', array_filter($icons));
            echo '<a href="index.php?id=' . $res['id'] . '">' . (empty($res['text']) ? '-----' : $res['text']) . '</a>&#160;[' . $colmes1 . ']';

            if ($cpg > 1) {
                echo '&#160;<a href="index.php?id=' . $res['id'] . '&amp;clip&amp;page=' . $cpg . '">&gt;&gt;</a>';
            }

            echo '<br><div class="sub"><a href="index.php?id=' . $razd['id'] . '">' . $frm['text'] . '&#160;/&#160;' . $razd['text'] . '</a><br />';
            echo $res['from'];

            if (!empty($nam['from'])) {
                echo '&#160;/&#160;' . $nam['from'];
            }

            echo ' <span class="gray">' . date("d.m.y / H:i", $nam['time']) . '</span>';
            echo '</div></div>';
        }
    } else {
        echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
    }
    echo '<div class="phdr"><a href="index.php">' . _t('Forum') . '</a></div>';
}
