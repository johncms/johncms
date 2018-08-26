<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                Mobile Content Management System                    //
// Project site:          http://johncms.com                                  //
// Support site:          http://gazenwagen.com                               //
////////////////////////////////////////////////////////////////////////////////
// Lead Developer:        Oleg Kasyanov   (AlkatraZ)  alkatraz@gazenwagen.com //
// Development Team:      Eugene Ryabinin (john77)    john77@gazenwagen.com   //
//                        Dmitry Liseenko (FlySelf)   flyself@johncms.com     //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');

/*
-----------------------------------------------------------------
Подробная информация, контактные данные
-----------------------------------------------------------------
*/
$textl = htmlspecialchars($user['name']) . ': ' . $lng['information'];
require('../incfiles/head.php');
echo '<div class="phdr"><a href="profile.php?user=' . $user['id'] . '"><b>' . $lng['profile'] . '</b></a> | ' . $lng['information'] . '</div>';
if ($user['id'] == $user_id || ($rights >= 7 && $rights > $user['rights']))
    echo '<div class="topmenu"><a href="profile.php?act=edit&amp;user=' . $user['id'] . '">' . $lng['edit'] . '</a></div>';
echo '<div class="user"><p>' . functions::display_user($user, array ('iphide' => 1,)) . '</p></div>' .
    '<div class="list2"><p>' .
    '<h3><img src="../images/contacts.png" width="16" height="16" class="left" />&#160;' . $lng_profile['personal_data'] . '</h3>' .
    '<ul>';
if (file_exists('../files/users/photo/' . $user['id'] . '_small.jpg'))
    echo '<a href="../files/users/photo/' . $user['id'] . '.jpg"><img src="../files/users/photo/' . $user['id'] . '_small.jpg" alt="' . $user['name'] . '" border="0" /></a>';
echo '<li><span class="gray">' . $lng_profile['name'] . ':</span> ' . (empty($user['imname']) ? '' : _e($user['imname'])) . '</li>' .
    '<li><span class="gray">' . $lng_profile['birt'] . ':</span> ' . (empty($user['dayb']) ? '' : sprintf("%02d", $user['dayb']) . '.' . sprintf("%02d", $user['monthb']) . '.' . $user['yearofbirth']) . '</li>' .
    '<li><span class="gray">' . $lng_profile['city'] . ':</span> ' . (empty($user['live']) ? '' : $user['live']) . '</li>' .
    '<li><span class="gray">' . $lng_profile['about'] . ':</span> ' . (empty($user['about']) ? '' : '<br />' . functions::smileys(bbcode::tags(_e($user['about'])))) . '</li>' .
    '</ul></p><p>' .
    '<h3><img src="../images/mail.png" width="16" height="16" class="left" />&#160;' . $lng_profile['communication'] . '</h3><ul>' .
    '<li><span class="gray">' . $lng_profile['phone_number'] . ':</span> ' . (empty($user['mibile']) ? '' : $user['mibile']) . '</li>' .
    '<li><span class="gray">E-mail:</span> ';
if (!empty($user['mail']) && $user['mailvis'] || $rights >= 7 || $user['id'] == $user_id) {
    echo $user['mail'] . ($user['mailvis'] ? '' : '<span class="gray"> [' . $lng_profile['hidden'] . ']</span>');
}
echo '</li>' .
    '<li><span class="gray">ICQ:</span> ' . (empty($user['icq']) ? '' : $user['icq']) . '</li>' .
    '<li><span class="gray">Skype:</span> ' . (empty($user['skype']) ? '' : $user['skype']) . '</li>' .
    '<li><span class="gray">Jabber:</span> ' . (empty($user['jabber']) ? '' : $user['jabber']) . '</li>' .
    '<li><span class="gray">' . $lng_profile['site'] . ':</span> ' . (empty($user['www']) ? '' : bbcode::tags($user['www'])) . '</li>' .
    '</ul></p></div>' .
    '<div class="phdr"><a href="profile.php?user=' . $user['id'] . '">' . $lng['back'] . '</a></div>';
?>