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
            $ids = $db->query("SELECT `forum_topic`.`id`, `forum_topic`.`last_post_date`
            FROM `forum_topic` LEFT JOIN `cms_forum_rdm` ON `forum_topic`.`id` = `cms_forum_rdm`.`topic_id` AND `cms_forum_rdm`.`user_id` = '" . $systemUser->id . "'
            WHERE `forum_topic`.`last_post_date` > `cms_forum_rdm`.`time` OR `cms_forum_rdm`.`topic_id` IS NULL")->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($ids)) {
                foreach ($ids as $val) {
                    $values[] = '(' . $val['id'] . ', ' . $systemUser->id . ', ' . $val['last_post_date'] . ')';
                }
                $db->query('INSERT INTO cms_forum_rdm (topic_id, user_id, `time`) VALUES ' . implode(',', $values) . '
                    ON DUPLICATE KEY UPDATE `time` = VALUES(`time`)');
            }
            
            echo '<div class="menu"><p>' . _t('All topics marked as read') . '<br /><a href="index.php">' . _t('Forum') . '</a></p></div>';
            break;

        case 'period':
            // Показ новых тем за выбранный период
            $vr = isset($_REQUEST['vr']) ? abs(intval($_REQUEST['vr'])) : 24;
            $vr1 = time() - $vr * 3600;

            if ($systemUser->rights == 9) {
                $req = $db->query("SELECT COUNT(*) FROM `forum_topic` WHERE `mod_last_post_date` > '$vr1'");
            } else {
                $req = $db->query("SELECT COUNT(*) FROM `forum_topic` WHERE `last_post_date` > '$vr1' AND (`deleted` != '1' OR deleted IS NULL)");
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
                    $req = $db->query("SELECT * FROM `forum_topic` WHERE `mod_last_post_date` > '" . $vr1 . "' ORDER BY `mod_last_post_date` DESC LIMIT " . $start . "," . $kmess);
                } else {
                    $req = $db->query("SELECT * FROM `forum_topic` WHERE `last_post_date` > '" . $vr1 . "' AND (`deleted` != '1' OR deleted IS NULL) ORDER BY `last_post_date` DESC LIMIT " . $start . "," . $kmess);
                }

                for ($i = 0; $res = $req->fetch(); ++$i) {
                    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                    $razd = $db->query("SELECT `id`, `parent`, `name` FROM `forum_sections` WHERE `id`='" . $res['section_id'] . "'")->fetch();
                    $frm = $db->query("SELECT `name` FROM `forum_sections` WHERE `id`='" . $razd['parent'] . "'")->fetch();
                    $colmes1 = $systemUser->rights >= 7  ? $res['mod_post_count'] : $res['post_count'];
                    $cpg = ceil($colmes1 / $kmess);


                    if ($res['closed']) {
                        echo $tools->image('tz.gif');
                    } elseif ($res['deleted']) {
                        echo $tools->image('dl.gif');
                    } else {
                        echo $tools->image('np.gif');
                    }

                    if($res['pinned']) {
                        echo $tools->image('pt.gif');
                    }

                    if ($res['has_poll'] == 1) {
                        echo $tools->image('rate.gif');
                    }

                    echo '&#160;<a href="index.php?type=topic&id=' . $res['id'] . ($cpg > 1 && $set_forum['upfp'] && $set_forum['postclip'] ? '&amp;clip' : '') . ($set_forum['upfp'] && $cpg > 1 ? '&amp;page=' . $cpg : '') . '">' . (empty($res['name']) ? '-----' : $res['name']) .
                        '</a>&#160;[' . $colmes1 . ']';
                    if ($cpg > 1) {
                        echo '<a href="index.php?type=topic&id=' . $res['id'] . (!$set_forum['upfp'] && $set_forum['postclip'] ? '&amp;clip' : '') . ($set_forum['upfp'] ? '' : '&amp;page=' . $cpg) . '">&#160;&gt;&gt;</a>';
                    }

                    echo '<br /><div class="sub"><a href="index.php?type=topics&id=' . $razd['id'] . '">' . $frm['name'] . '&#160;/&#160;' . $razd['name'] . '</a><br />';

                    echo $res['user_name'];

                    if ($colmes1 > 1) {
                        echo '&#160;/&#160;' . ($systemUser->rights >= 7  ? $res['mod_last_post_author_name'] : $res['last_post_author_name']);
                    }

                    echo ' <span class="gray">' . $tools->displayDate(($systemUser->rights >= 7  ? $res['mod_last_post_date'] : $res['last_post_date'])) . '</span>';
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
                $req = $db->query("SELECT * FROM `forum_topic`
                LEFT JOIN `cms_forum_rdm` ON `forum_topic`.`id` = `cms_forum_rdm`.`topic_id` AND `cms_forum_rdm`.`user_id` = '" . $systemUser->id . "'
                WHERE " . ($systemUser->rights >= 7 ? "" : "(`forum_topic`.`deleted` != '1' OR `forum_topic`.`deleted` IS NULL) AND ") . "
                (`cms_forum_rdm`.`topic_id` Is Null
                OR `forum_topic`.`last_post_date` > `cms_forum_rdm`.`time`)
                ORDER BY `forum_topic`.`last_post_date` DESC
                LIMIT $start, $kmess");

                for ($i = 0; $res = $req->fetch(); ++$i) {
                    if ($res['deleted']) {
                        echo '<div class="rmenu">';
                    } else {
                        echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                    }

                    $razd = $db->query("SELECT `id`, `parent`, `name` FROM `forum_sections` WHERE `id` = '" . $res['section_id'] . "' LIMIT 1")->fetch();
                    $frm = $db->query("SELECT `id`, `name` FROM `forum_sections` WHERE `id` = '" . $razd['parent'] . "' LIMIT 1")->fetch();

                    $post_count = $systemUser->rights >= 7 ? $res['mod_post_count'] : $res['post_count'];
                    $cpg = ceil($post_count / $kmess);

                    // Значки
                    $icons = [
                        (isset($np) ? (!$res['pinned'] ? $tools->image('op.gif') : '') : $tools->image('np.gif')),
                        ($res['pinned'] ? $tools->image('pt.gif') : ''),
                        ($res['has_poll'] ? $tools->image('rate.gif') : ''),
                        ($res['closed'] ? $tools->image('tz.gif') : ''),
                    ];
                    echo implode('', array_filter($icons));
                    echo '<a href="index.php?type=topic&id=' . $res['id'] . ($cpg > 1 && $set_forum['upfp'] && $set_forum['postclip'] ? '&amp;clip' : '') . ($set_forum['upfp'] && $cpg > 1 ? '&amp;page=' . $cpg : '') . '">' . (empty($res['name']) ? '-----' : $res['name']) .
                        '</a>&#160;[' . $post_count . ']';

                    if ($cpg > 1) {
                        echo '&#160;<a href="index.php?type=topic&id=' . $res['id'] . (!$set_forum['upfp'] && $set_forum['postclip'] ? '&amp;clip' : '') . ($set_forum['upfp'] ? '' : '&amp;page=' . $cpg) . '">&gt;&gt;</a>';
                    }

                    $last_author = $systemUser->rights >= 7 ? $res['mod_last_post_author_name'] : $res['last_post_author_name'];
                    $last_post_date = $systemUser->rights >= 7 ? $res['mod_last_post_date'] : $res['last_post_date'];

                    echo '<div class="sub">' . $res['user_name'] . ($post_count > 1 ? '&#160;/&#160;' . $last_author : '') .
                        ' <span class="gray">(' . $tools->displayDate($last_post_date) . ')</span><br />' .
                        '<a href="index.php?id=' . $frm['id'] . '">' . $frm['name'] . '</a>&#160;/&#160;<a href="index.php?type=topics&id=' . $razd['id'] . '">' . $razd['name'] . '</a>' .
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
    $req = $db->query("SELECT * FROM `forum_topic` WHERE (`deleted` != '1' OR deleted IS NULL) ORDER BY `last_post_date` DESC LIMIT 10");

    if ($req->rowCount()) {
        for ($i = 0; $res = $req->fetch(); ++$i) {
            $razd = $db->query("SELECT `id`, `parent`, `name` FROM `forum_sections` WHERE id='" . $res['section_id'] . "' LIMIT 1")->fetch();
            $frm = $db->query("SELECT `id`, `parent`, `name` FROM `forum_sections` WHERE id='" . $razd['parent'] . "' LIMIT 1")->fetch();
            $colmes1 = $res['post_count'];
            $cpg = ceil($colmes1 / $kmess);
            echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
            // Значки
            $icons = [
                ($res['pinned'] ? $tools->image('pt.gif') : ''),
                ($res['has_poll'] ? $tools->image('rate.gif') : ''),
                ($res['closed'] ? $tools->image('tz.gif') : ''),
            ];
            echo implode('', array_filter($icons));
            echo '<a href="index.php?type=topic&id=' . $res['id'] . '">' . (empty($res['name']) ? '-----' : $res['name']) . '</a>&#160;[' . $colmes1 . ']';

            if ($cpg > 1) {
                echo '&#160;<a href="index.php?type=topic&id=' . $res['id'] . '&amp;clip&amp;page=' . $cpg . '">&gt;&gt;</a>';
            }

            echo '<br><div class="sub"><a href="index.php?type=topics&id=' . $razd['id'] . '">' . $frm['name'] . '&#160;/&#160;' . $razd['name'] . '</a><br />';
            echo $res['user_name'];

            if (!empty($res['last_post_author_name'])) {
                echo '&#160;/&#160;' . $res['last_post_author_name'];
            }

            echo ' <span class="gray">' . date("d.m.y / H:i", $res['last_post_date']) . '</span>';
            echo '</div></div>';
        }
    } else {
        echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
    }
    echo '<div class="phdr"><a href="index.php">' . _t('Forum') . '</a></div>';
}
