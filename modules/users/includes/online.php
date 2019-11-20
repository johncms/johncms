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

ob_start();

/** @var Johncms\Api\EnvironmentInterface $env */
$env = di(Johncms\Api\EnvironmentInterface::class);

// Показываем список Online
$menu[] = ! $mod ? '<b>' . _t('Users') . '</b>' : '<a href="?act=online">' . _t('Users') . '</a>';
$menu[] = $mod == 'history' ? '<b>' . _t('History') . '</b>' : '<a href="?act=online&amp;mod=history">' . _t('History') . '</a> ';

if ($user->rights) {
    $menu[] = $mod == 'guest' ? '<b>' . _t('Guests') . '</b>' : '<a href="?act=online&amp;mod=guest">' . _t('Guests') . '</a>';
    $menu[] = $mod == 'ip' ? '<b>' . _t('IP Activity') . '</b>' : '<a href="?act=online&amp;mod=ip">' . _t('IP Activity') . '</a>';
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
            $start = max(0, $total - (($total % $user->config->kmess) == 0 ? $user->config->kmess : ($total % $user->config->kmess)));
        }

        $end = $start + $user->config->kmess;

        if ($end > $total) {
            $end = $total;
        }

        arsort($ip_array);
        $i = 0;

        foreach ($ip_array as $key => $val) {
            $ip_list[$i] = [$key => $val];
            ++$i;
        }

        if ($total && $user->rights) {
            if ($total > $user->config->kmess) {
                echo '<div class="topmenu">' . $tools->displayPagination('?act=online&amp;mod=ip&amp;', $start, $total, $user->config->kmess) . '</div>';
            }

            for ($i = $start; $i < $end; $i++) {
                $ipLong = key($ip_list[$i]);
                $ip = long2ip($ipLong);

                if ($ipLong == di(Johncms\Api\EnvironmentInterface::class)->getIp()) {
                    echo '<div class="gmenu">';
                } else {
                    echo ($i % 2) ? '<div class="list2">' : '<div class="list1">';
                }

                echo '[' . $ip_list[$i][$ipLong] . ']&#160;&#160;<a href="' . $config->homeurl . '/admin/?act=search_ip&amp;ip=' . $ip . '">' . $ip . '</a>' .
                    '&#160;&#160;<small>[<a href="' . $config->homeurl . '/admin/?act=ip_whois&amp;ip=' . $ip . '">?</a>]</small></div>';
            }

            echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

            if ($total > $user->config->kmess) {
                echo '<div class="topmenu">' . $tools->displayPagination('?act=online&amp;mod=ip&amp;', $start, $total, $user->config->kmess) . '</div>' .
                    '<p><form action="?act=online&amp;mod=ip" method="post">' .
                    '<input type="text" name="page" size="2"/>' .
                    '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
            }
        }

        echo $view->render('system::app/old_content', [
            'title'   => _t('Online'),
            'content' => ob_get_clean(),
        ]);
        exit;

    case 'guest':
        // Список гостей Онлайн
        $sql_total = 'SELECT COUNT(*) FROM `cms_sessions` WHERE `lastdate` > ' . (time() - 300);
        $sql_list = 'SELECT * FROM `cms_sessions` WHERE `lastdate` > ' . (time() - 300) . ' ORDER BY `movings` DESC LIMIT ';
        break;

    case 'history':
        // История посетилелей за последние 2 суток
        $sql_total = 'SELECT COUNT(*) FROM `users` WHERE `lastdate` > ' . (time() - 172800 . ' AND `lastdate` < ' . (time() - 310));
        $sql_list = 'SELECT * FROM `users` WHERE `lastdate` > ' . (time() - 172800) . ' AND `lastdate` < ' . (time() - 310) . ' ORDER BY `sestime` DESC LIMIT ';
        break;

    default:
        // Список посетителей Онлайн
        $sql_total = 'SELECT COUNT(*) FROM `users` WHERE `lastdate` > ' . (time() - 300);
        $sql_list = 'SELECT * FROM `users` WHERE `lastdate` > ' . (time() - 300) . ' ORDER BY `name` ASC LIMIT ';
}

$total = $db->query($sql_total)->fetchColumn();

// Исправляем запрос на несуществующую страницу
if ($start >= $total) {
    $start = max(0, $total - (($total % $user->config->kmess) == 0 ? $user->config->kmess : ($total % $user->config->kmess)));
}

if ($total > $user->config->kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('?act=online&amp;' . ($mod ? 'mod=' . $mod . '&amp;' : ''), $start, $total, $user->config->kmess) . '</div>';
}

if ($total) {
    $req = $db->query($sql_list . "${start}, " . $user->config->kmess);
    $i = 0;

    while ($res = $req->fetch()) {
        $res['id'] = $res['id'] ?? 0;

        if ($res['id'] == $user->id) {
            echo '<div class="gmenu">';
        } else {
            echo ($i % 2) ? '<div class="list2">' : '<div class="list1">';
        }

        $arg['stshide'] = 1;
        $arg['header'] = ' <span class="gray">(';

        if ($mod == 'history') {
            $arg['header'] .= $tools->displayDate($res['sestime']);
        } else {
            $arg['header'] .= $res['movings'] . ' - ' . $tools->timecount(time() - $res['sestime']);
        }

        $arg['header'] .= ')</span><br /><img src="' . $assets->url('images/old/info.png') . '" alt="" class="icon">' . $tools->displayPlace($res['place'], (int) $res['id']);
        echo $tools->displayUser($res, $arg);
        echo '</div>';
        ++$i;
    }
} else {
    echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
}

echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

if ($total > $user->config->kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('?act=online&amp;' . ($mod ? 'mod=' . $mod . '&amp;' : ''), $start, $total, $user->config->kmess) . '</div>' .
        '<p><form action="?act=online' . ($mod ? '&amp;mod=' . $mod : '') . '" method="post">' .
        '<input type="text" name="page" size="2"/>' .
        '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
        '</form></p>';
}

echo $view->render('system::app/old_content', [
    'title'   => _t('Online'),
    'content' => ob_get_clean(),
]);
