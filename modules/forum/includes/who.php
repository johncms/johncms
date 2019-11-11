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

/**
 * @var PDO                        $db
 * @var Johncms\Api\ToolsInterface $tools
 * @var Johncms\Api\UserInterface  $user
 */

$textl = _t('Who in Forum');

if (! $user->isValid()) {
    header('Location: ./');
    exit;
}

if ($id) {
    // Показываем общий список тех, кто в выбранной теме
    $req = $db->query("SELECT `name` FROM `forum_topic` WHERE `id` = '${id}'");

    if ($req->rowCount()) {
        $res = $req->fetch();
        echo '<div class="phdr"><b>' . _t('Who in Topic') . ':</b> <a href="?type=topic&id=' . $id . '">' . $res['name'] . '</a></div>';

        if ($user->rights > 0) {
            echo '<div class="topmenu">' .
                ($do == 'guest' ? '<a href="?act=who&amp;id=' . $id . '">' . _t('Authorized') . '</a> | ' . _t('Guests') : _t('Authorized') . ' | <a href="?act=who&amp;do=guest&amp;id=' . $id . '">' . _t('Guests') . '</a>') .
                '</div>';
        }

        $total = $db->query('SELECT COUNT(*) FROM `' . ($do == 'guest' ? 'cms_sessions' : 'users') . '` WHERE `lastdate` > ' . (time() - 300) . " AND `place` LIKE '/forum?type=topic&id=${id}%'")->fetchColumn();

        if ($start >= $total) {
            // Исправляем запрос на несуществующую страницу
            $start = max(0, $total - (($total % $user->config->kmess) == 0 ? $user->config->kmess : ($total % $user->config->kmess)));
        }

        if ($total > $user->config->kmess) {
            echo '<div class="topmenu">' . $tools->displayPagination('?act=who&amp;id=' . $id . '&amp;' . ($do == 'guest' ? 'do=guest&amp;' : ''), $start, $total, $user->config->kmess) . '</div>';
        }

        if ($total) {
            $req = $db->query('SELECT * FROM `' . ($do == 'guest' ? 'cms_sessions' : 'users') . '` WHERE `lastdate` > ' . (time() - 300) . " AND `place` LIKE '/forum?type=topic&id=${id}%' ORDER BY " . ($do == 'guest' ? '`movings` DESC' : '`name` ASC') . " LIMIT ${start}, " . $user->config->kmess);

            for ($i = 0; $res = $req->fetch(); ++$i) {
                echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                $set_user['avatar'] = 0;
                echo $tools->displayUser($res,
                    ['iphide' => ($act == 'guest' || ($user->rights >= 1 && $user->rights >= $res['rights']) ? 0 : 1)]);
                echo '</div>';
            }
        } else {
            echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
        }
    } else {
        header('Location: ./');
    }

    echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

    if ($total > $user->config->kmess) {
        echo '<div class="topmenu">' . $tools->displayPagination('?act=who&amp;id=' . $id . '&amp;' . ($do == 'guest' ? 'do=guest&amp;' : ''), $start, $total, $user->config->kmess) . '</div>' .
            '<p><form action="?act=who&amp;id=' . $id . ($do == 'guest' ? '&amp;do=guest' : '') . '" method="post">' .
            '<input type="text" name="page" size="2"/>' .
            '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
            '</form></p>';
    }

    echo '<p><a href="?type=topic&id=' . $id . '">' . _t('Go to Topic') . '</a></p>';
} else {
    // Показываем общий список тех, кто в форуме
    echo '<div class="phdr"><a href="./"><b>' . _t('Forum') . '</b></a> | ' . _t('Who in Forum') . '</div>';

    if ($user->rights > 0) {
        echo '<div class="topmenu">' . ($do == 'guest' ? '<a href="?act=who">' . _t('Users') . '</a> | <b>' . _t('Guests') . '</b>'
                : '<b>' . _t('Users') . '</b> | <a href="?act=who&amp;do=guest">' . _t('Guests') . '</a>') . '</div>';
    }

    $total = $db->query('SELECT COUNT(*) FROM `' . ($do == 'guest' ? 'cms_sessions' : 'users') . '` WHERE `lastdate` > ' . (time() - 300) . " AND `place` LIKE '/forum%'")->fetchColumn();

    if ($start >= $total) {
        // Исправляем запрос на несуществующую страницу
        $start = max(0, $total - (($total % $user->config->kmess) == 0 ? $user->config->kmess : ($total % $user->config->kmess)));
    }

    if ($total > $user->config->kmess) {
        echo '<div class="topmenu">' . $tools->displayPagination('?act=who&amp;' . ($do == 'guest' ? 'do=guest&amp;' : ''), $start, $total, $user->config->kmess) . '</div>';
    }

    if ($total) {
        $req = $db->query('SELECT * FROM `' . ($do == 'guest' ? 'cms_sessions' : 'users') . '` WHERE `lastdate` > ' . (time() - 300) . " AND `place` LIKE '/forum%' ORDER BY " . ($do == 'guest' ? '`movings` DESC' : '`name` ASC') . " LIMIT ${start}, " . $user->config->kmess);

        for ($i = 0; $res = $req->fetch(); ++$i) {
            if ($res['id'] == $user->id) {
                echo '<div class="gmenu">';
            } else {
                echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
            }

            // Вычисляем местоположение
            $place = '';
            $parsed_url = [];
            if (! empty($res['place'])) {
                $parsed_url = parse_url($res['place']);
                if (! empty($parsed_url['query'])) {
                    parse_str($parsed_url['query'], $parsed_url);
                }
            }

            $place_id = 0;
            $act_type = '';
            $place = 'forum';

            if (! empty($parsed_url['act'])) {
                $place = $parsed_url['act'];
                $place_id = $parsed_url['id'] ?? 0;
                $act_type = $parsed_url['type'] ?? '';
            } elseif (! empty($parsed_url['type'])) {
                $place = $parsed_url['type'];
                $place_id = $parsed_url['id'];
            } elseif (! empty($parsed_url['id'])) {
                $place = 'section';
                $place_id = $parsed_url['id'];
            }

            switch ($place) {
                case 'forum':
                    $place = '<a href="./">' . _t('In the forum Main') . '</a>';
                    break;

                case 'who':
                    $place = _t('Here, in the List');
                    break;

                case 'files':
                    $place = '<a href="?act=files">' . _t('Looking forum files') . '</a>';
                    break;

                case 'new':
                    $place = '<a href="?act=new">' . _t('In the unreads') . '</a>';
                    break;

                case 'search':
                    $place = '<a href="search.php">' . _t('Forum search') . '</a>';
                    break;

                case 'section':
                    $section = $db->query('SELECT * FROM `forum_sections` WHERE `id`= ' . $place_id)->fetch();
                    if (! empty($section)) {
                        $link = '<a href="?id=' . $section['id'] . '">' . (empty($section['name']) ? '-----' : $section['name']) . '</a>';
                        $place = _t('In the Category') . ' &quot;' . $link . '&quot;';
                    } else {
                        $place = '<a href="./">' . _t('In the forum Main') . '</a>';
                    }
                    break;

                case 'topics':
                    $topics = $db->query('SELECT * FROM `forum_sections` WHERE `id`= ' . $place_id)->fetch();
                    if (! empty($topics)) {
                        $link = '<a href="?type=topics&id=' . $topics['id'] . '">' . (empty($topics['name']) ? '-----' : $topics['name']) . '</a>';
                        $place = _t('In the Section') . ' &quot;' . $link . '&quot;';
                    } else {
                        $place = '<a href="./">' . _t('In the forum Main') . '</a>';
                    }
                    break;

                case 'say':
                case 'topic':
                    $topic = $db->query('SELECT * FROM `forum_topic` WHERE `id`= ' . $place_id)->fetch();
                    if (! empty($topic)) {
                        $link = '<a href="?type=topic&id=' . $topic['id'] . '">' . (empty($topic['name']) ? '-----' : $topic['name']) . '</a>';

                        if ($act_type == 'reply') {
                            $place = _t('Answers in the Topic') . ' &quot;' . $link . '&quot;';
                        } else {
                            $place = (($place == 'say') ? _t('Writes in the Topic') . ' &quot;' : _t('In the Topic') . ' &quot;') . $link . '&quot;';
                        }
                    } else {
                        $place = '<a href="./">' . _t('In the forum Main') . '</a>';
                    }
                    break;

                case 'show_post':
                    $message = $db->query("SELECT * FROM `forum_messages` WHERE `id` = '" . $place_id . "'")->fetch();
                    if (! empty($message)) {
                        $req_m = $db->query("SELECT * FROM `forum_topic` WHERE `id` = '" . $message['topic_id'] . "'");
                        if ($req_m->rowCount()) {
                            $res_m = $req_m->fetch();
                            $place = _t('In the Topic') . ' &quot;<a href="?type=topic&id=' . $res_m['id'] . '">' . (empty($res_m['name']) ? '-----' : $res_m['name']) . '</a>&quot;';
                        }
                    }
                    break;

                default:
                    $place = '<a href="./">' . _t('In the forum Main') . '</a>';
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

    if ($total > $user->config->kmess) {
        echo '<div class="topmenu">' . $tools->displayPagination('?act=who&amp;' . ($do == 'guest' ? 'do=guest&amp;' : ''), $start, $total, $user->config->kmess) . '</div>' .
            '<p><form action="?act=who' . ($do == 'guest' ? '&amp;do=guest' : '') . '" method="post">' .
            '<input type="text" name="page" size="2"/>' .
            '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
            '</form></p>';
    }
    echo '<p><a href="./">' . _t('Forum') . '</a></p>';
}
