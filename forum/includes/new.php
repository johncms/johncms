<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

$textl = $lng['forum'] . ' | ' . $lng['unread'];
$headmod = 'forumnew';
require('../incfiles/head.php');
unset($_SESSION['fsort_id']);
unset($_SESSION['fsort_users']);
if (empty($_SESSION['uid'])) {
    if (isset($_GET['newup'])) {
        $_SESSION['uppost'] = 1;
    }
    if (isset($_GET['newdown'])) {
        $_SESSION['uppost'] = 0;
    }
}
if ($user_id) {
    switch ($do) {
        case 'reset':
            /*
            -----------------------------------------------------------------
            Отмечаем все темы как прочитанные
            -----------------------------------------------------------------
            */
            $stmt = $db->query("SELECT `forum`.`id`
            FROM `forum` LEFT JOIN `cms_forum_rdm` ON `forum`.`id` = `cms_forum_rdm`.`topic_id` AND `cms_forum_rdm`.`user_id` = '$user_id'
            WHERE `forum`.`type`='t'
            AND `cms_forum_rdm`.`topic_id` IS Null");
            while ($res = $stmt->fetch()) {
                $db->exec("INSERT INTO `cms_forum_rdm` SET
                    `topic_id` = '" . $res['id'] . "',
                    `user_id` = '$user_id',
                    `time` = '" . time() . "'
                ");
            }
            $stmt = $db->query("SELECT `forum`.`id` AS `id`
            FROM `forum` LEFT JOIN `cms_forum_rdm` ON `forum`.`id` = `cms_forum_rdm`.`topic_id` AND `cms_forum_rdm`.`user_id` = '$user_id'
            WHERE `forum`.`type`='t'
            AND `forum`.`time` > `cms_forum_rdm`.`time`");
            while ($res = $stmt->fetch()) {
                $db->exec("UPDATE `cms_forum_rdm` SET
                    `time` = '" . time() . "'
                    WHERE `topic_id` = '" . $res['id'] . "' AND `user_id` = '$user_id'
                ");
            }
            echo '<div class="menu"><p>' . $lng_forum['unread_reset_done'] . '<br /><a href="index.php">' . $lng_forum['to_forum'] . '</a></p></div>';
            break;

        case 'period':
            /*
            -----------------------------------------------------------------
            Показ новых тем за выбранный период
            -----------------------------------------------------------------
            */
            $vr = isset($_REQUEST['vr']) ? abs(intval($_REQUEST['vr'])) : 24;
            $vr1 = time() - $vr * 3600;
            if ($rights == 9) {
                $stmt = $db->query("SELECT COUNT(*) FROM `forum` WHERE `type`='t' AND `time` > '$vr1'");
            } else {
                $stmt = $db->query("SELECT COUNT(*) FROM `forum` WHERE `type`='t' AND `time` > '$vr1' AND `close` != '1'");
            }
            $count = $stmt->fetchColumn();

            echo '<div class="phdr"><a href="index.php"><b>' . $lng['forum'] . '</b></a> | ' . $lng_forum['unread_all_for_period'] . ' ' . $vr . ' ' . $lng_forum['hours'] . '</div>';

            // Форма выбора периода времени
            echo '<div class="topmenu"><form action="index.php?act=new&amp;do=period" method="post">' .
                '<input type="text" maxlength="3" name="vr" value="' . $vr . '" size="3"/>' .
                '<input type="submit" name="submit" value="' . $lng['show_for_period'] . '"/>' .
                '</form></div>';

            if ($count > $kmess) {
                echo '<div class="topmenu">' . functions::display_pagination('index.php?act=new&amp;do=period&amp;vr=' . $vr . '&amp;', $start, $count, $kmess) . '</div>';
            }

            if ($count > 0) {
                if ($rights == 9) {
                    $stmt = $db->query("SELECT * FROM `forum` WHERE `type`='t' AND `time` > '" . $vr1 . "' ORDER BY `time` DESC LIMIT " . $start . "," . $kmess);
                } else {
                    $stmt = $db->query("SELECT * FROM `forum` WHERE `type`='t' AND `time` > '" . $vr1 . "' AND `close` != '1' ORDER BY `time` DESC LIMIT " . $start . "," . $kmess);
                }
                $i = 0;
                while ($res = $stmt->fetch()) {
                    echo ++$i % 2 ? '<div class="list2">' : '<div class="list1">';
                    $razd = $db->query("SELECT `id`, `refid`, `text` FROM `forum` WHERE `type`='r' AND `id`='" . $res['refid'] . "' LIMIT 1")->fetch();
                    $frm = $db->query("SELECT `text` FROM `forum` WHERE `type`='f' AND `id`='" . $razd['refid'] . "' LIMIT 1")->fetch();
                    $colmes1 = $db->query("SELECT COUNT(*) FROM `forum` WHERE `refid` = '" . $res['id'] . "' AND `type` = 'm'" . ($rights >= 7 ? '' : " AND `close` != '1'"))->fetchColumn();
                    $nick = $db->query("SELECT * FROM `forum` WHERE `refid` = '" . $res['id'] . "' AND `type` = 'm'" . ($rights >= 7 ? '' : " AND `close` != '1'") . " ORDER BY `time` DESC LIMIT 1");
                    $cpg = ceil($colmes1 / $kmess);

                    if ($res['edit']) {
                        echo functions::image('tz.gif');
                    } elseif ($res['close']) {
                        echo functions::image('dl.gif');
                    } else {
                        echo functions::image('np.gif');
                    }

                    if ($res['realid'] == 1) {
                        echo functions::image('rate.gif');
                    }

                    echo '&#160;<a href="index.php?id=' . $res['id'] . ($cpg > 1 && $set_forum['upfp'] && $set_forum['postclip'] ? '&amp;clip' : '') . ($set_forum['upfp'] && $cpg > 1 ? '&amp;page=' . $cpg : '') . '">' . _e($res['text']) .
                        '</a>&#160;[' . $colmes1 . ']';
                    if ($cpg > 1) {
                        echo '<a href="index.php?id=' . $res['id'] . (!$set_forum['upfp'] && $set_forum['postclip'] ? '&amp;clip' : '') . ($set_forum['upfp'] ? '' : '&amp;page=' . $cpg) . '">&#160;&gt;&gt;</a>';
                    }

                    echo '<br /><div class="sub"><a href="index.php?id=' . $razd['id'] . '">' . _e($frm['text']) . '&#160;/&#160;' . _e($razd['text']) . '</a><br />';
                    echo $res['from'];

                    if ($colmes1 > 1) {
                        echo '&#160;/&#160;' . $nick['from'];
                    }

                    echo ' <span class="gray">' . functions::display_date($nick['time']) . '</span>';
                    echo '</div></div>';
                }
            } else {
                echo '<div class="menu"><p>' . $lng_forum['unread_period_empty'] . '</p></div>';
            }
            echo '<div class="phdr">' . $lng['total'] . ': ' . $count . '</div>';
            if ($count > $kmess) {
                echo '<div class="topmenu">' . functions::display_pagination('index.php?act=new&amp;do=period&amp;vr=' . $vr . '&amp;', $start, $count, $kmess) . '</div>' .
                    '<p><form action="index.php?act=new&amp;do=period&amp;vr=' . $vr . '" method="post">
                    <input type="text" name="page" size="2"/>
                    <input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
            }
            break;

        default:
            /*
            -----------------------------------------------------------------
            Вывод непрочитанных тем (для зарегистрированных)
            -----------------------------------------------------------------
            */
            $total = counters::forum_new();
            echo '<div class="phdr"><a href="index.php"><b>' . $lng['forum'] . '</b></a> | ' . $lng['unread'] . '</div>';
            if ($total > $kmess)
                echo '<div class="topmenu">' . functions::display_pagination('index.php?act=new&amp;', $start, $total, $kmess) . '</div>';
            if ($total > 0) {
                $stmt = $db->query("SELECT * FROM `forum`
                LEFT JOIN `cms_forum_rdm` ON `forum`.`id` = `cms_forum_rdm`.`topic_id` AND `cms_forum_rdm`.`user_id` = '$user_id'
                WHERE `forum`.`type`='t'" . ($rights >= 7 ? "" : " AND `forum`.`close` != '1'") . "
                AND (`cms_forum_rdm`.`topic_id` Is Null
                OR `forum`.`time` > `cms_forum_rdm`.`time`)
                ORDER BY `forum`.`time` DESC
                LIMIT $start, $kmess");
                $i = 0;
                while ($res = $stmt->fetch()) {
                    if ($res['close']) {
                        echo '<div class="rmenu">';
                    } else {
                        echo ++$i % 2 ? '<div class="list2">' : '<div class="list1">';
                    }
                    $razd = $db->query("SELECT `id`, `refid`, `text` FROM `forum` WHERE `type` = 'r' AND `id` = '" . $res['refid'] . "' LIMIT 1")->fetch();
                    $frm = $db->query("SELECT `id`, `text` FROM `forum` WHERE `type`='f' AND `id` = '" . $razd['refid'] . "' LIMIT 1")->fetch();
                    $colmes1 = $db->query("SELECT COUNT(*) FROM `forum` WHERE `refid` = '" . $res['id'] . "' AND `type` = 'm'" . ($rights >= 7 ? '' : " AND `close` != '1'"))->fetchColumn();
                    $nick = $db->query("SELECT `from`, `time` FROM `forum` WHERE `refid` = '" . $res['id'] . "' AND `type` = 'm'" . ($rights >= 7 ? '' : " AND `close` != '1'") . " ORDER BY `time` DESC LIMIT 1")->fetch();
                    $cpg = ceil($colmes1 / $kmess);
                    // Значки
                    $icons = array(
                        (isset($np) ? (!$res['vip'] ? functions::image('op.gif') : '') : functions::image('np.gif')),
                        ($res['vip'] ? functions::image('pt.gif') : ''),
                        ($res['realid'] ? functions::image('rate.gif') : ''),
                        ($res['edit'] ? functions::image('tz.gif') : '')
                    );
                    echo functions::display_menu($icons, '');
                    echo '<a href="index.php?id=' . $res['id'] . ($cpg > 1 && $set_forum['upfp'] && $set_forum['postclip'] ? '&amp;clip' : '') . ($set_forum['upfp'] && $cpg > 1 ? '&amp;page=' . $cpg : '') . '">' . _e($res['text']) .
                        '</a>&#160;[' . $colmes1 . ']';
                    if ($cpg > 1) {
                        echo '&#160;<a href="index.php?id=' . $res['id'] . (!$set_forum['upfp'] && $set_forum['postclip'] ? '&amp;clip' : '') . ($set_forum['upfp'] ? '' : '&amp;page=' . $cpg) . '">&gt;&gt;</a>';
                    }
                    echo '<div class="sub">' . $res['from'] . ($colmes1 > 1 ? '&#160;/&#160;' . $nick['from'] : '') .
                        ' <span class="gray">(' . functions::display_date($nick['time']) . ')</span><br />' .
                        '<a href="index.php?id=' . $frm['id'] . '">' . _e($frm['text']) . '</a>&#160;/&#160;<a href="index.php?id=' . $razd['id'] . '">' . _e($razd['text']) . '</a>' .
                        '</div></div>';
                }
            } else {
                echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
            }
            echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
            if ($total > $kmess) {
                echo '<div class="topmenu">' . functions::display_pagination('index.php?act=new&amp;', $start, $total, $kmess) . '</div>' .
                    '<p><form action="index.php" method="get">' .
                    '<input type="hidden" name="act" value="new"/>' .
                    '<input type="text" name="page" size="2"/>' .
                    '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/>' .
                    '</form></p>';
            }

            if ($total) {
                echo '<p><a href="index.php?act=new&amp;do=reset">' . $lng_forum['unread_reset'] . '</a></p>';
            }

    }
} else {
    /*
    -----------------------------------------------------------------
    Вывод 10 последних тем (для незарегистрированных)
    -----------------------------------------------------------------
    */
    echo '<div class="phdr"><a href="index.php"><b>' . $lng['forum'] . '</b></a> | ' . $lng_forum['unread_last_10'] . '</div>';
    $stmt = $db->query("SELECT * FROM `forum` WHERE `type` = 't' AND `close` != '1' ORDER BY `time` DESC LIMIT 10");
    if ($stmt->rowCount()) {
        $i = 0;
        while ($res = $stmt->fetch()) {
            $razd = $db->query("select `id`, `refid`, `text` from `forum` where type='r' and id='" . $res['refid'] . "' LIMIT 1")->fetch();
            $frm = $db->query("select `id`, `refid`, `text` from `forum` where type='f' and id='" . $razd['refid'] . "' LIMIT 1")->fetch();
            $colmes1 = $db->query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'm' AND `close` != '1' AND `refid` = '" . $res['id'] . "'")->fetchColumn();
            $nam = $db->query("SELECT `from`, `time` FROM `forum` WHERE `type` = 'm' AND `close` != '1' AND `refid` = '" . $res['id'] . "'ORDER BY `time` DESC LIMIT 1")->fetch();
            $cpg = ceil($colmes1 / $kmess);
            echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
            // Значки
            $icons = array(
                ($res['vip'] ? functions::image('pt.gif') : ''),
                ($res['realid'] ? functions::image('rate.gif') : ''),
                ($res['edit'] ? functions::image('tz.gif') : '')
            );
            echo functions::display_menu($icons, '');
            echo '<a href="index.php?id=' . $res['id'] . '">' . _e($res['text']) . '</a>&#160;[' . $colmes1 . ']';
            if ($cpg > 1)
                echo '&#160;<a href="index.php?id=' . $res['id'] . '&amp;clip&amp;page=' . $cpg . '">&gt;&gt;</a>';
            echo '<br/><div class="sub"><a href="index.php?id=' . $razd['id'] . '">' . _e($frm['text']) . '&#160;/&#160;' . _e($razd['text']) . '</a><br />';
            echo $res['from'];
            if (!empty($nam['from'])) {
                echo '&#160;/&#160;' . $nam['from'];
            }
            echo ' <span class="gray">' . date("d.m.y / H:i", $nam['time']) . '</span>';
            echo '</div></div>';
        }
    } else {
        echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
    }
    echo '<div class="phdr"><a href="index.php">' . $lng['to_forum'] . '</a></div>';
}