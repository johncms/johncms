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

$textl = _t('Who in Forum');
$headmod = $id ? 'forum,' . $id . ',topic' : 'forumwho';
require_once '../system/head.php';

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

if (! $systemUser->isValid()) {
    header('Location: index.php');
    exit;
}

if ($id) {
    // Показываем общий список тех, кто в выбранной теме
    $req = $db->query("SELECT `name` FROM `forum_topic` WHERE `id` = '${id}'");

    if ($req->rowCount()) {
        $res = $req->fetch();
        echo '<div class="phdr"><b>' . _t('Who in Topic') . ':</b> <a href="index.php?type=topic&id=' . $id . '">' . $res['name'] . '</a></div>';

        if ($systemUser->rights > 0) {
            echo '<div class="topmenu">' .
                ($do == 'guest' ? '<a href="index.php?act=who&amp;id=' . $id . '">' . _t('Authorized') . '</a> | ' . _t('Guests') : _t('Authorized') . ' | <a href="index.php?act=who&amp;do=guest&amp;id=' . $id . '">' . _t('Guests') . '</a>') .
                '</div>';
        }

        $total = $db->query('SELECT COUNT(*) FROM `' . ($do == 'guest' ? 'cms_sessions' : 'users') . '` WHERE `lastdate` > ' . (time() - 300) . " AND `place` = 'forum,${id},topic'")->fetchColumn();

        if ($start >= $total) {
            // Исправляем запрос на несуществующую страницу
            $start = max(0, $total - (($total % $kmess) == 0 ? $kmess : ($total % $kmess)));
        }

        if ($total > $kmess) {
            echo '<div class="topmenu">' . $tools->displayPagination('index.php?act=who&amp;id=' . $id . '&amp;' . ($do == 'guest' ? 'do=guest&amp;' : ''), $start, $total, $kmess) . '</div>';
        }

        if ($total) {
            $req = $db->query('SELECT * FROM `' . ($do == 'guest' ? 'cms_sessions' : 'users') . '` WHERE `lastdate` > ' . (time() - 300) . " AND `place` LIKE 'forum,${id},topic' ORDER BY " . ($do == 'guest' ? '`movings` DESC' : '`name` ASC') . " LIMIT ${start}, ${kmess}");

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

    echo '<p><a href="index.php?type=topic&id=' . $id . '">' . _t('Go to Topic') . '</a></p>';
} else {
    // Показываем общий список тех, кто в форуме
    echo '<div class="phdr"><a href="index.php"><b>' . _t('Forum') . '</b></a> | ' . _t('Who in Forum') . '</div>';

    if ($systemUser->rights > 0) {
        echo '<div class="topmenu">' . ($do == 'guest' ? '<a href="index.php?act=who">' . _t('Users') . '</a> | <b>' . _t('Guests') . '</b>'
                : '<b>' . _t('Users') . '</b> | <a href="index.php?act=who&amp;do=guest">' . _t('Guests') . '</a>') . '</div>';
    }

    $total = $db->query('SELECT COUNT(*) FROM `' . ($do == 'guest' ? 'cms_sessions' : 'users') . '` WHERE `lastdate` > ' . (time() - 300) . " AND `place` LIKE 'forum%'")->fetchColumn();

    if ($start >= $total) {
        // Исправляем запрос на несуществующую страницу
        $start = max(0, $total - (($total % $kmess) == 0 ? $kmess : ($total % $kmess)));
    }

    if ($total > $kmess) {
        echo '<div class="topmenu">' . $tools->displayPagination('index.php?act=who&amp;' . ($do == 'guest' ? 'do=guest&amp;' : ''), $start, $total, $kmess) . '</div>';
    }

    if ($total) {
        $req = $db->query('SELECT * FROM `' . ($do == 'guest' ? 'cms_sessions' : 'users') . '` WHERE `lastdate` > ' . (time() - 300) . " AND `place` LIKE 'forum%' ORDER BY " . ($do == 'guest' ? '`movings` DESC' : '`name` ASC') . " LIMIT ${start}, ${kmess}");

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
                    $where = explode(',', $res['place']);
                    if ($where[0] == 'forum' && (int) ($where[1]) && ! empty($where[2])) {
                        switch ($where[2]) {
                            case 'section':
                                $section = $db->query('SELECT * FROM `forum_sections` WHERE `id`= ' . $where[1])->fetch();
                                if (! empty($section)) {
                                    $link = '<a href="index.php?id=' . $section['id'] . '">' . (empty($section['name']) ? '-----' : $section['name']) . '</a>';
                                    $place = _t('In the Category') . ' &quot;' . $link . '&quot;';
                                } else {
                                    $place = '<a href="index.php">' . _t('In the forum Main') . '</a>';
                                }
                                break;

                            case 'topics':
                                $topics = $db->query('SELECT * FROM `forum_sections` WHERE `id`= ' . $where[1])->fetch();
                                if (! empty($topics)) {
                                    $link = '<a href="index.php?type=topics&id=' . $topics['id'] . '">' . (empty($topics['name']) ? '-----' : $topics['name']) . '</a>';
                                    $place = _t('In the Section') . ' &quot;' . $link . '&quot;';
                                } else {
                                    $place = '<a href="index.php">' . _t('In the forum Main') . '</a>';
                                }
                                break;

                            case 'topic':
                                $topic = $db->query('SELECT * FROM `forum_topic` WHERE `id`= ' . $where[1])->fetch();
                                if (! empty($topic)) {
                                    $link = '<a href="index.php?type=topic&id=' . $topic['id'] . '">' . (empty($topic['name']) ? '-----' : $topic['name']) . '</a>';
                                    $place = (isset($where[3]) ? _t('Writes in the Topic') . ' &quot;' : _t('In the Topic') . ' &quot;') . $link . '&quot;';
                                } else {
                                    $place = '<a href="index.php">' . _t('In the forum Main') . '</a>';
                                }
                                break;

                            case 'message':
                                $message = $db->query("SELECT * FROM `forum_messages` WHERE `id` = '" . $where[1] . "'")->fetch();
                                if (! empty($message)) {
                                    $req_m = $db->query("SELECT * FROM `forum_topic` WHERE `id` = '" . $message['topic_id'] . "'");
                                    if ($req_m->rowCount()) {
                                        $res_m = $req_m->fetch();
                                        $place = (isset($where[2]) ? _t('Answers in the Topic') : _t('In the Topic')) . ' &quot;<a href="index.php?type=topic&id=' . $res_m['id'] . '">' . (empty($res_m['name']) ? '-----' : $res_m['name']) . '</a>&quot;';
                                    }
                                }
                                break;
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
