<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

// Статистика
$textl = htmlspecialchars($user['name']) . ': ' . _td('Statistic');
require('../incfiles/head.php');
echo '<div class="phdr"><a href="?user=' . $user['id'] . '"><b>' . _td('Profile') . '</b></a> | ' . _td('Statistic') . '</div>' .
    '<div class="user"><p>' . functions::display_user($user, ['iphide' => 1,]) . '</p></div>' .
    '<div class="list2">' .
    '<p><h3>' . functions::image('rate.gif') . _td('Statistic') . '</h3><ul>';

if ($rights >= 7) {
    if (!$user['preg'] && empty($user['regadm'])) {
        echo '<li>' . _td('Pending confirmation') . '</li>';
    } elseif ($user['preg'] && !empty($user['regadm'])) {
        echo '<li>' . _td('Registration confirmed') . ': ' . $user['regadm'] . '</li>';
    } else {
        echo '<li>' . _td('Free registration') . '</li>';
    }
}

echo '<li><span class="gray">' . _td('Registered') . ':</span> ' . date("d.m.Y",
        $user['datereg']) . '</li>';
$lastvisit = time() > $user['lastdate'] + 300 ? date("d.m.Y (H:i)", $user['lastdate']) : false;

if ($lastvisit) {
    echo '<li><span class="gray">' . _td('Last visit') . ':</span> ' . $lastvisit . '</li>';
}

echo '</ul></p><p>' .
    '<h3>' . functions::image('activity.gif') . _td('Activity') . '</h3><ul>' .
    '<li><span class="gray">' . _td('Forum') . ':</span> <a href="?act=activity&amp;user=' . $user['id'] . '">' . $user['postforum'] . '</a></li>' .
    '<li><span class="gray">' . _td('Guestbook') . ':</span> <a href="?act=activity&amp;mod=comments&amp;user=' . $user['id'] . '">' . $user['postguest'] . '</a></li>' .
    '<li><span class="gray">' . _td('Comments') . ':</span> ' . $user['komm'] . '</li>' .
    '</ul></p>' .
    '<p><h3>' . functions::image('award.png') . _td('Achievements') . '</h3>';
$num = [
    50,
    100,
    500,
    1000,
    5000,
];
$query = [
    'postforum' => _td('Forum'),
    'postguest' => _td('Guestbook'),
    'komm'      => _td('Comments'),
];
echo '<table border="0" cellspacing="0" cellpadding="0"><tr>';

foreach ($num as $val) {
    echo '<td width="28" align="center"><small>' . $val . '</small></td>';
}

echo '<td></td></tr>';

foreach ($query as $key => $val) {
    echo '<tr>';
    foreach ($num as $achieve) {
        echo '<td align="center">' . functions::image(($user[$key] >= $achieve ? 'green' : 'red') . '.gif') . '</td>';
    }
    echo '<td><small><b>' . $val . '</b></small></td></tr>';
}
echo '</table></p></div><div class="phdr"><a href="?user=' . $user['id'] . '">' . _td('Back') . '</a></div>';
