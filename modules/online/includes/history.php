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
$menu[] = '<a href="../">' . _t('Users') . '</a>';
$menu[] = '<strong>' . _t('History') . '</strong>';

if ($user->rights) {
    $menu[] = '<a href="../guest/">' . _t('Guests') . '</a>';
    $menu[] = '<a href="../ip/">' . _t('IP Activity') . '</a>';
}

echo '<div class="phdr"><b>' . _t('Who is online?') . '</b></div><div class="topmenu">' . implode(' | ', $menu) . '</div>';

$total = $db->query('SELECT COUNT(*) FROM `users` WHERE `lastdate` > ' . (time() - 172800 . ' AND `lastdate` < ' . (time() - 310)))->fetchColumn();

// Исправляем запрос на несуществующую страницу
if ($start >= $total) {
    $start = max(0, $total - (($total % $user->config->kmess) == 0 ? $user->config->kmess : ($total % $user->config->kmess)));
}

if ($total > $user->config->kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('?', $start, $total, $user->config->kmess) . '</div>';
}

if ($total) {
    $req = $db->query('SELECT * FROM `users` WHERE `lastdate` > ' . (time() - 172800) . ' AND `lastdate` < ' . (time() - 310) . " ORDER BY `sestime` DESC LIMIT ${start}, " . $user->config->kmess);
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
        $arg['header'] .= $tools->displayDate($res['sestime']);
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
    echo '<div class="topmenu">' . $tools->displayPagination('?', $start, $total, $user->config->kmess) . '</div>';
}

echo $view->render('system::app/old_content', [
    'title'   => _t('Online'),
    'content' => ob_get_clean(),
]);
