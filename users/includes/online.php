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

$headmod = 'online';
$textl = _t('Online');
require('../system/head.php');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\EnvironmentInterface $env */
$env = App::getContainer()->get(Johncms\Api\EnvironmentInterface::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

// Показываем список Online
$menu[] = !$mod ? '<b>' . _t('Users') . '</b>' : '<a href="index.php?act=online">' . _t('Users') . '</a>';
$menu[] = $mod == 'history' ? '<b>' . _t('History') . '</b>' : '<a href="index.php?act=online&amp;mod=history">' . _t('History') . '</a> ';

if ($systemUser->rights) {
    $menu[] = $mod == 'guest' ? '<b>' . _t('Guests') . '</b>' : '<a href="index.php?act=online&amp;mod=guest">' . _t('Guests') . '</a>';
    $menu[] = $mod == 'ip' ? '<b>' . _t('IP Activity') . '</b>' : '<a href="index.php?act=online&amp;mod=ip">' . _t('IP Activity') . '</a>';
}

echo '<div class="phdr"><b>' . _t('Who is online?') . '</b></div>' .
    '<div class="topmenu">' . implode(' | ', $menu) . '</div>';

switch ($mod) {
    case 'ip':
        // Список активных IP, со счетчиком обращений
        $ip_array = array_count_values($env->getIpLog());
        $total = count($ip_array);

        if ($start >= $total) {
            // Исправляем запрос на несуществующую страницу
            $start = max(0, $total - (($total % $kmess) == 0 ? $kmess : ($total % $kmess)));
        }

        $end = $start + $kmess;

        if ($end > $total) {
            $end = $total;
        }

        arsort($ip_array);
        $i = 0;

        foreach ($ip_array as $key => $val) {
            $ip_list[$i] = [$key => $val];
            ++$i;
        }

        if ($total && $systemUser->rights) {
            if ($total > $kmess) {
                echo '<div class="topmenu">' . $tools->displayPagination('index.php?act=online&amp;mod=ip&amp;', $start, $total, $kmess) . '</div>';
            }

            for ($i = $start; $i < $end; $i++) {
                $out = each($ip_list[$i]);
                $ip = long2ip($out[0]);

                if ($out[0] == $container->get(Johncms\Api\EnvironmentInterface::class)->getIp()) {
                    echo '<div class="gmenu">';
                } else {
                    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                }

                echo '[' . $out[1] . ']&#160;&#160;<a href="' . $config->homeurl . '/admin/index.php?act=search_ip&amp;ip=' . $ip . '">' . $ip . '</a>' .
                    '&#160;&#160;<small>[<a href="' . $config->homeurl . '/admin/index.php?act=ip_whois&amp;ip=' . $ip . '">?</a>]</small></div>';
            }

            echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

            if ($total > $kmess) {
                echo '<div class="topmenu">' . $tools->displayPagination('index.php?act=online&amp;mod=ip&amp;', $start, $total, $kmess) . '</div>' .
                    '<p><form action="index.php?act=online&amp;mod=ip" method="post">' .
                    '<input type="text" name="page" size="2"/>' .
                    '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
            }
        }

        require_once('../system/end.php');
        exit;
        break;

    case 'guest':
        // Список гостей Онлайн
        $sql_total = "SELECT COUNT(*) FROM `cms_sessions` WHERE `lastdate` > " . (time() - 300);
        $sql_list = "SELECT * FROM `cms_sessions` WHERE `lastdate` > " . (time() - 300) . " ORDER BY `movings` DESC LIMIT ";
        break;

    case 'history':
        // История посетилелей за последние 2 суток
        $sql_total = "SELECT COUNT(*) FROM `users` WHERE `lastdate` > " . (time() - 172800 . " AND `lastdate` < " . (time() - 310));
        $sql_list = "SELECT * FROM `users` WHERE `lastdate` > " . (time() - 172800) . " AND `lastdate` < " . (time() - 310) . " ORDER BY `sestime` DESC LIMIT ";
        break;

    default:
        // Список посетителей Онлайн
        $sql_total = "SELECT COUNT(*) FROM `users` WHERE `lastdate` > " . (time() - 300);
        $sql_list = "SELECT * FROM `users` WHERE `lastdate` > " . (time() - 300) . " ORDER BY `name` ASC LIMIT ";
}

$total = $db->query($sql_total)->fetchColumn();

// Исправляем запрос на несуществующую страницу
if ($start >= $total) {
    $start = max(0, $total - (($total % $kmess) == 0 ? $kmess : ($total % $kmess)));
}

if ($total > $kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('index.php?act=online&amp;' . ($mod ? 'mod=' . $mod . '&amp;' : ''), $start, $total, $kmess) . '</div>';
}

if ($total) {
    $req = $db->query($sql_list . "$start, $kmess");
    $i = 0;

    while ($res = $req->fetch()) {
        if ($res['id'] == $systemUser->id) {
            echo '<div class="gmenu">';
        } else {
            echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
        }

        $arg['stshide'] = 1;
        $arg['header'] = ' <span class="gray">(';

        if ($mod == 'history') {
            $arg['header'] .= $tools->displayDate($res['sestime']);
        } else {
            $arg['header'] .= $res['movings'] . ' - ' . $tools->timecount(time() - $res['sestime']);
        }

        $arg['header'] .= ')</span><br /><img src="../images/info.png" width="16" height="16" align="middle" />&#160;' . $tools->displayPlace($res['id'], $res['place'], $headmod);
        echo $tools->displayUser($res, $arg);
        echo '</div>';
        ++$i;
    }
} else {
    echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
}

echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

if ($total > $kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('index.php?act=online&amp;' . ($mod ? 'mod=' . $mod . '&amp;' : ''), $start, $total, $kmess) . '</div>' .
        '<p><form action="index.php?act=online' . ($mod ? '&amp;mod=' . $mod : '') . '" method="post">' .
        '<input type="text" name="page" size="2"/>' .
        '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
        '</form></p>';
}
