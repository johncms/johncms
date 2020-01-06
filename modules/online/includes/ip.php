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

/** @var Johncms\System\Http\Environment $env */
$env = di(Johncms\System\Http\Environment::class);

// Показываем список Online
$menu[] = '<a href="../">' . _t('Users') . '</a>';
$menu[] = '<a href="../history/">' . _t('History') . '</a> ';

if ($user->rights) {
    $menu[] = '<a href="../guest/">' . _t('Guests') . '</a>';
    $menu[] = '<strong>' . _t('IP Activity') . '</strong>';
}

echo '<div class="phdr"><b>' . _t('Who is online?') . '</b></div><div class="topmenu">' . implode(' | ', $menu) . '</div>';

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
        echo '<div class="topmenu">' . $tools->displayPagination('?', $start, $total, $user->config->kmess) . '</div>';
    }

    for ($i = $start; $i < $end; $i++) {
        $ipLong = key($ip_list[$i]);
        $ip = long2ip($ipLong);

        if ($ipLong == di(Johncms\System\Http\Environment::class)->getIp()) {
            echo '<div class="gmenu">';
        } else {
            echo ($i % 2) ? '<div class="list2">' : '<div class="list1">';
        }

        echo '[' . $ip_list[$i][$ipLong] . ']&#160;&#160;<a href="' . $config['homeurl'] . '/admin/?act=search_ip&amp;ip=' . $ip . '">' . $ip . '</a>' .
            '&#160;&#160;<small>[<a href="' . $config['homeurl'] . '/admin/?act=ip_whois&amp;ip=' . $ip . '">?</a>]</small></div>';
    }

    echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

    if ($total > $user->config->kmess) {
        echo '<div class="topmenu">' . $tools->displayPagination('?', $start, $total, $user->config->kmess) . '</div>';
    }
}

echo $view->render('system::app/old_content', [
    'title'   => _t('Online'),
    'content' => ob_get_clean(),
]);
