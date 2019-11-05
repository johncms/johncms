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

// Статистика
$textl = htmlspecialchars($foundUser['name']) . ': ' . _t('Statistic');
echo '<div class="phdr"><a href="?user=' . $foundUser['id'] . '"><b>' . _t('Profile') . '</b></a> | ' . _t('Statistic') . '</div>' .
    '<div class="user"><p>' . $tools->displayUser($foundUser, ['iphide' => 1]) . '</p></div>' .
    '<div class="list2">' .
    '<p><h3>' . $tools->image('rate.gif') . _t('Statistic') . '</h3><ul>';

if ($user->rights >= 7) {
    if (! $foundUser['preg'] && empty($foundUser['regadm'])) {
        echo '<li>' . _t('Pending confirmation') . '</li>';
    } elseif ($foundUser['preg'] && ! empty($foundUser['regadm'])) {
        echo '<li>' . _t('Registration confirmed') . ': ' . $foundUser['regadm'] . '</li>';
    } else {
        echo '<li>' . _t('Free registration') . '</li>';
    }
}

echo '<li><span class="gray">' . _t('Registered') . ':</span> ' . date('d.m.Y', (int) $foundUser['datereg']) . '</li>';
echo '<li><span class="gray">' . ($foundUser['sex'] == 'm' ? _t('He stay on the site') : _t('She stay on the site')) . ':</span> ' . $tools->timecount((int) $foundUser['total_on_site']) . '</li>';
$lastvisit = time() > $foundUser['lastdate'] + 300 ? date('d.m.Y (H:i)', $foundUser['lastdate']) : false;

if ($lastvisit) {
    echo '<li><span class="gray">' . _t('Last visit') . ':</span> ' . $lastvisit . '</li>';
}

echo '</ul></p><p>' .
    '<h3>' . $tools->image('activity.gif') . _t('Activity') . '</h3><ul>' .
    '<li><span class="gray">' . _t('Forum') . ':</span> <a href="?act=activity&amp;user=' . $foundUser['id'] . '">' . $foundUser['postforum'] . '</a></li>' .
    '<li><span class="gray">' . _t('Guestbook') . ':</span> <a href="?act=activity&amp;mod=comments&amp;user=' . $foundUser['id'] . '">' . $foundUser['postguest'] . '</a></li>' .
    '<li><span class="gray">' . _t('Comments') . ':</span> ' . $foundUser['komm'] . '</li>' .
    '</ul></p>' .
    '<p><h3>' . $tools->image('award.png') . _t('Achievements') . '</h3>';
$num = [
    50,
    100,
    500,
    1000,
    5000,
];
$query = [
    'postforum' => _t('Forum'),
    'postguest' => _t('Guestbook'),
    'komm'      => _t('Comments'),
];
echo '<table border="0" cellspacing="0" cellpadding="0"><tr>';

foreach ($num as $val) {
    echo '<td width="28" align="center"><small>' . $val . '</small></td>';
}

echo '<td></td></tr>';

foreach ($query as $key => $val) {
    echo '<tr>';

    foreach ($num as $achieve) {
        echo '<td align="center"><img src="' . $assets->url('images/old/' . ($foundUser[$key] >= $achieve ? 'green' : 'red') . '.gif') . '" alt="">' . '</td>';
    }

    echo '<td><small><b>' . $val . '</b></small></td></tr>';
}

echo '</table></p></div><div class="phdr"><a href="?user=' . $foundUser['id'] . '">' . _t('Back') . '</a></div>';
