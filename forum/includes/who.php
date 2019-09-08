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

$textl = _t('Who in Forum');
$headmod = $id ? 'forum,' . $id : 'forumwho';
require_once('../system/head.php');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

if (!$systemUser->isValid()) {
    header('Location: index.php');
    exit;
}

if ($id) {
    // Показываем общий список тех, кто в выбранной теме
    $req = $db->query("SELECT `text` FROM `forum` WHERE `id` = '$id' AND `type` = 't'");

    if ($req->rowCount()) {
        $res = $req->fetch();
        echo '<div class="phdr"><b>' . _t('Who in Topic') . ':</b> <a href="index.php?id=' . $id . '">' . $res['text'] . '</a></div>';

        if ($systemUser->rights > 0) {
            echo '<div class="topmenu">' .
                ($do == 'guest' ? '<a href="index.php?act=who&amp;id=' . $id . '">' . _t('Authorized') . '</a> | ' . _t('Guests') : _t('Authorized') . ' | <a href="index.php?act=who&amp;do=guest&amp;id=' . $id . '">' . _t('Guests') . '</a>') .
                '</div>';
        }

        $total = $db->query("SELECT COUNT(*) FROM `" . ($do == 'guest' ? 'cms_sessions' : 'users') . "` WHERE `lastdate` > " . (time() - 300) . " AND `place` = 'forum,$id'")->fetchColumn();

        if ($start >= $total) {
            // Исправляем запрос на несуществующую страницу
            $start = max(0, $total - (($total % $kmess) == 0 ? $kmess : ($total % $kmess)));
        }

        if ($total > $kmess) {
            echo '<div class="topmenu">' . $tools->displayPagination('index.php?act=who&amp;id=' . $id . '&amp;' . ($do == 'guest' ? 'do=guest&amp;' : ''), $start, $total, $kmess) . '</div>';
        }

        if ($total) {
            $req = $db->query("SELECT * FROM `" . ($do == 'guest' ? 'cms_sessions' : 'users') . "` WHERE `lastdate` > " . (time() - 300) . " AND `place` = 'forum,$id' ORDER BY " . ($do == 'guest' ? "`movings` DESC" : "`name` ASC") . " LIMIT $start, $kmess");

            for ($i = 0; $res = $req->fetch(); ++$i) {
                echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                $set_user['avatar'] = 0;
                echo $tools->displayUser($res, ['iphide' => ($act == 'guest' || ($systemUser->rights >= 1 && $systemUser->rights >= $res['rights']) ? 0 : 1)]);
                echo '</div>';
            }
        } else {
            echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
        }
    } else {
        header('Location: index.php');
    }

    echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

    if ($total > $kmess) {
        echo '<div class="topmenu">' . $tools->displayPagination('index.php?act=who&amp;id=' . $id . '&amp;' . ($do == 'guest' ? 'do=guest&amp;' : ''), $start, $total, $kmess) . '</div>' .
            '<p><form action="index.php?act=who&amp;id=' . $id . ($do == 'guest' ? '&amp;do=guest' : '') . '" method="post">' .
            '<input type="text" name="page" size="2"/>' .
            '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
            '</form></p>';
    }

    echo '<p><a href="index.php?id=' . $id . '">' . _t('Go to Topic') . '</a></p>';
} else {
    // Показываем общий список тех, кто в форуме
    echo '<div class="phdr"><a href="index.php"><b>' . _t('Forum') . '</b></a> | ' . _t('Who in Forum') . '</div>';

    if ($systemUser->rights > 0) {
        echo '<div class="topmenu">' . ($do == 'guest' ? '<a href="index.php?act=who">' . _t('Users') . '</a> | <b>' . _t('Guests') . '</b>'
                : '<b>' . _t('Users') . '</b> | <a href="index.php?act=who&amp;do=guest">' . _t('Guests') . '</a>') . '</div>';
    }

    $total = $db->query("SELECT COUNT(*) FROM `" . ($do == 'guest' ? "cms_sessions" : "users") . "` WHERE `lastdate` > " . (time() - 300) . " AND `place` LIKE 'forum%'")->fetchColumn();

    if ($start >= $total) {
        // Исправляем запрос на несуществующую страницу
        $start = max(0, $total - (($total % $kmess) == 0 ? $kmess : ($total % $kmess)));
    }

    if ($total > $kmess) {
        echo '<div class="topmenu">' . $tools->displayPagination('index.php?act=who&amp;' . ($do == 'guest' ? 'do=guest&amp;' : ''), $start, $total, $kmess) . '</div>';
    }

    if ($total) {
        $req = $db->query("SELECT * FROM `" . ($do == 'guest' ? "cms_sessions" : "users") . "` WHERE `lastdate` > " . (time() - 300) . " AND `place` LIKE 'forum%' ORDER BY " . ($do == 'guest' ? "`movings` DESC" : "`name` ASC") . " LIMIT $start, $kmess");

        for ($i = 0; $res = $req->fetch(); ++$i) {
            if ($res['id'] == $systemUser->id) {
                echo '<div class="gmenu">';
            } else {
                echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
            }

            // Вычисляем местоположение
            $place = '';

            switch ($res['place']) {
                case 'forum':
                    $place = '<a href="index.php">' . _t('In the forum Main') . '</a>';
                    break;

                case 'forumwho':
                    $place = _t('Here, in the List');
                    break;

                case 'forumfiles':
                    $place = '<a href="index.php?act=files">' . _t('Looking forum files') . '</a>';
                    break;

                case 'forumnew':
                    $place = '<a href="index.php?act=new">' . _t('In the unreads') . '</a>';
                    break;

                case 'forumsearch':
                    $place = '<a href="search.php">' . _t('Forum search') . '</a>';
                    break;

                default:
                    $where = explode(",", $res['place']);
                    if ($where[0] == 'forum' && intval($where[1])) {
                        $req_t = $db->query("SELECT `type`, `refid`, `text` FROM `forum` WHERE `id` = '$where[1]'");

                        if ($req_t->rowCount()) {
                            $res_t = $req_t->fetch();
                            $link = '<a href="index.php?id=' . $where[1] . '">' . (empty($res_t['text']) ? '-----' : $res_t['text']) . '</a>';

                            switch ($res_t['type']) {
                                case 'f':
                                    $place = _t('In the Category') . ' &quot;' . $link . '&quot;';
                                    break;

                                case 'r':
                                    $place = _t('In the Section') . ' &quot;' . $link . '&quot;';
                                    break;

                                case 't':
                                    $place = (isset($where[2]) ? _t('Writes in the Topic') . ' &quot;' : _t('In the Topic') . ' &quot;') . $link . '&quot;';
                                    break;

                                case 'm':
                                    $req_m = $db->query("SELECT `text` FROM `forum` WHERE `id` = '" . $res_t['refid'] . "' AND `type` = 't'");

                                    if ($req_m->rowCount()) {
                                        $res_m = $req_m->fetch();
                                        $place = (isset($where[2]) ? _t('Answers in the Topic') : _t('In the Topic')) . ' &quot;<a href="index.php?id=' . $res_t['refid'] . '">' . (empty($res_m['text']) ? '-----' : $res_m['text']) . '</a>&quot;';
                                    }

                                    break;
                            }
                        }
                    }
            }

            $arg = [
                'stshide' => 1,
                'header'  => ('<br /><img src="../images/info.png" width="16" height="16" align="middle" />&#160;' . $place),
            ];
            echo $tools->displayUser($res, $arg);
            echo '</div>';
        }
    } else {
        echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
    }

    echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

    if ($total > $kmess) {
        echo '<div class="topmenu">' . $tools->displayPagination('index.php?act=who&amp;' . ($do == 'guest' ? 'do=guest&amp;' : ''), $start, $total, $kmess) . '</div>' .
            '<p><form action="index.php?act=who' . ($do == 'guest' ? '&amp;do=guest' : '') . '" method="post">' .
            '<input type="text" name="page" size="2"/>' .
            '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
            '</form></p>';
    }
    echo '<p><a href="index.php">' . _t('Forum') . '</a></p>';
}
