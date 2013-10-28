<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

/*
-----------------------------------------------------------------
Статистика
-----------------------------------------------------------------
*/
$textl = htmlspecialchars($user['name']) . ': ' . $lng['statistics'];
require('../incfiles/head.php');
echo'<div class="phdr"><a href="profile.php?user=' . $user['id'] . '"><b>' . $lng['profile'] . '</b></a> | ' . $lng['statistics'] . '</div>' .
    '<div class="user"><p>' . functions::display_user($user, array('iphide' => 1,)) . '</p></div>' .
    '<div class="list2">' .
    '<p><h3>' . functions::image('rate.gif') . $lng['statistics'] . '</h3><ul>';
if ($rights >= 7) {
    if (!$user['preg'] && empty($user['regadm']))
        echo '<li>' . $lng_profile['awaiting_registration'] . '</li>';
    elseif ($user['preg'] && !empty($user['regadm']))
        echo '<li>' . $lng_profile['registration_approved'] . ': ' . $user['regadm'] . '</li>'; else
        echo '<li>' . $lng_profile['registration_free'] . '</li>';
}
echo'<li><span class="gray">' . ($user['sex'] == 'm' ? $lng_profile['registered_m'] : $lng_profile['registered_w']) . ':</span> ' . date("d.m.Y", $user['datereg']) . '</li>' .
    '<li><span class="gray">' . ($user['sex'] == 'm' ? $lng_profile['stayed_m'] : $lng_profile['stayed_w']) . ':</span> ' . functions::timecount($user['total_on_site']) . '</li>';
$lastvisit = time() > $user['lastdate'] + 300 ? date("d.m.Y (H:i)", $user['lastdate']) : false;
if ($lastvisit)
    echo '<li><span class="gray">' . $lng['last_visit'] . ':</span> ' . $lastvisit . '</li>';
echo'</ul></p><p>' .
    '<h3>' . functions::image('activity.gif') . $lng_profile['activity'] . '</h3><ul>' .
    '<li><span class="gray">' . $lng['forum'] . ':</span> <a href="profile.php?act=activity&amp;user=' . $user['id'] . '">' . $user['postforum'] . '</a></li>' .
    '<li><span class="gray">' . $lng['guestbook'] . ':</span> <a href="profile.php?act=activity&amp;mod=comments&amp;user=' . $user['id'] . '">' . $user['postguest'] . '</a></li>' .
    '<li><span class="gray">' . $lng['comments'] . ':</span> ' . $user['komm'] . '</li>' .
    '</ul></p>' .
    '<p><h3>' . functions::image('award.png') . $lng_profile['achievements'] . '</h3>';
$num = array(
    50,
    100,
    500,
    1000,
    5000
);
$query = array(
    'postforum' => $lng['forum'],
    'postguest' => $lng['guestbook'],
    'komm' => $lng['comments']
);
echo '<table border="0" cellspacing="0" cellpadding="0"><tr>';
foreach ($num as $val) {
    echo '<td width="28" align="center"><small>' . $val . '</small></td>';
}
echo '<td></td></tr>';
foreach ($query as $key => $val) {
    echo '<tr>';
    foreach ($num as $achieve) {
        echo'<td align="center">' . functions::image(($user[$key] >= $achieve ? 'green' : 'red') . '.gif') . '</td>';
    }
    echo'<td><small><b>' . $val . '</b></small></td></tr>';
}
echo'</table></p></div>' .
    '<div class="phdr"><a href="profile.php?user=' . $user['id'] . '">' . $lng['back'] . '</a></div>';