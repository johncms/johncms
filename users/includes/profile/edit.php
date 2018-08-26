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

$textl = htmlspecialchars($user['name']) . ': ' . $lng_profile['profile_edit'];
require('../incfiles/head.php');

/*
-----------------------------------------------------------------
Проверяем права доступа для редактирования Профиля
-----------------------------------------------------------------
*/
if (!empty($ban) || $user['id'] != $user_id && ($rights < 7 || $user['rights'] >= $rights)) {
    echo functions::display_error($lng_profile['error_rights']);
    require('../incfiles/end.php');
    exit;
}

echo '<div class="phdr"><a href="profile.php?user=' . $user['id'] . '"><b>' . ($user['id'] != $user_id ? $lng['profile'] : $lng_profile['my_profile']) . '</b></a> | ' . $lng['edit'] . '</div>';

if (isset($_GET['delavatar']) && file_exists('../files/users/avatar/' . $user['id'] . '.png')) {
    /*
    -----------------------------------------------------------------
    Удаляем аватар
    -----------------------------------------------------------------
    */
    unlink('../files/users/avatar/' . $user['id'] . '.png');
    echo '<div class="rmenu">' . $lng_profile['avatar_deleted'] . '</div>';
} elseif (isset($_GET['delphoto']) && file_exists('../files/users/photo/' . $user['id'] . '_small.jpg')) {
    /*
    -----------------------------------------------------------------
    Удаляем фото
    -----------------------------------------------------------------
    */
    unlink('../files/users/photo/' . $user['id'] . '.jpg');
    unlink('../files/users/photo/' . $user['id'] . '_small.jpg');
    echo '<div class="rmenu">' . $lng_profile['photo_deleted'] . '</div>';
} elseif (isset($_POST['submit'])) {
    /*
    -----------------------------------------------------------------
    Принимаем данные из формы, проверяем и записываем в базу
    -----------------------------------------------------------------
    */
    $error = array();
    $user['imname'] = isset($_POST['imname']) ? trim(mb_substr($_POST['imname'], 0, 30)) : '';
    $user['live'] = isset($_POST['live']) ? _e(mb_substr($_POST['live'], 0, 50)) : '';
    $user['dayb'] = isset($_POST['dayb']) ? intval($_POST['dayb']) : 0;
    $user['monthb'] = isset($_POST['monthb']) ? intval($_POST['monthb']) : 0;
    $user['yearofbirth'] = isset($_POST['yearofbirth']) ? intval($_POST['yearofbirth']) : 0;
    $user['about'] = isset($_POST['about']) ? trim(mb_substr($_POST['about'], 0, 500)) : '';
    $user['mibile'] = isset($_POST['mibile']) ? _e(mb_substr($_POST['mibile'], 0, 40)) : '';
    $user['mail'] = isset($_POST['mail']) ? _e(mb_substr($_POST['mail'], 0, 40)) : '';
    $user['mailvis'] = isset($_POST['mailvis']) ? 1 : 0;
    $user['icq'] = isset($_POST['icq']) ? intval($_POST['icq']) : 0;
    $user['skype'] = isset($_POST['skype']) ? _e(mb_substr($_POST['skype'], 0, 40)) : '';
    $user['jabber'] = isset($_POST['jabber']) ? _e(mb_substr($_POST['jabber'], 0, 40)) : '';
    $user['www'] = isset($_POST['www']) ? _e(trim(mb_substr($_POST['www'], 0, 40))) : '';
    // Данные юзера (для Администраторов)
    $user['name'] = isset($_POST['name']) ? _e(mb_substr($_POST['name'], 0, 20)) : $user['name'];
    $user['status'] = isset($_POST['status']) ? _e(mb_substr($_POST['status'], 0, 50)) : '';
    $user['karma_off'] = isset($_POST['karma_off']) ? 1 : 0;
    $user['sex'] = isset($_POST['sex']) && $_POST['sex'] == 'm' ? 'm' : 'zh';
    $user['rights'] = isset($_POST['rights']) ? abs(intval($_POST['rights'])) : $user['rights'];
    // Проводим необходимые проверки
    if ($user['rights'] > $rights || $user['rights'] > 9 || $user['rights'] < 0) {
        $user['rights'] = 0;
    }
    if ($rights >= 7) {
        if (mb_strlen($user['name']) < 5 || mb_strlen($user['name']) > 20) {
            $error[] = $lng_profile['error_nick_lenght'];
        }
        $lat_nick = functions::rus_lat(mb_strtolower($user['name']));
        if (preg_match('/[^[:alnum]_.]/', $lat_nick)) {
            $error[] = $lng_profile['error_nick_symbols'];
        }
    }
    if ($user['dayb'] || $user['monthb'] || $user['yearofbirth']) {
        if ($user['dayb'] < 1 || $user['dayb'] > 31 || $user['monthb'] < 1 || $user['monthb'] > 12) {
            $error[] = $lng_profile['error_birth'];
        }
    }
    if ($user['icq'] && ($user['icq'] < 10000 || $user['icq'] > 999999999)) {
        $error[] = $lng_profile['error_icq'];
    }
    if (!$error) {
        $stmt = $db->prepare("UPDATE `users` SET
            `imname` = ?,
            `live` = ?,
            `dayb` = '" . $user['dayb'] . "',
            `monthb` = '" . $user['monthb'] . "',
            `yearofbirth` = '" . $user['yearofbirth'] . "',
            `about` = ?,
            `mibile` = ?,
            `mail` = ?,
            `mailvis` = '" . $user['mailvis'] . "',
            `icq` = '" . $user['icq'] . "',
            `skype` = ?,
            `jabber` = ?,
            `www` = ?
            WHERE `id` = '" . $user['id'] . "'
        ");
        $stmt->execute([
            $user['imname'],
            $user['live'],
            $user['about'],
            $user['mibile'],
            $user['mail'],
            $user['skype'],
            $user['jabber'],
            $user['www']
        ]);
        if ($rights >= 7) {
            $stmt = $db->prepare("UPDATE `users` SET
                `name` = ?,
                `status` = ?,
                `karma_off` = '" . $user['karma_off'] . "',
                `sex` = '" . $user['sex'] . "',
                `rights` = '" . $user['rights'] . "'
                WHERE `id` = '" . $user['id'] . "'
            ");
            $stmt->execute([
                $user['name'],
                $user['status']
            ]);
        }
        echo '<div class="gmenu">' . $lng_profile['data_saved'] . '</div>';
    } else {
        echo functions::display_error($error);
    }
    header('Location: profile.php?act=edit&user=' . $user['id']); exit;
}

/*
-----------------------------------------------------------------
Форма редактирования анкеты пользователя
-----------------------------------------------------------------
*/
echo '<form action="profile.php?act=edit&amp;user=' . $user['id'] . '" method="post">' .
    '<div class="gmenu"><p>' .
    $lng['login_name'] . ': <b>' . $user['name_lat'] . '</b><br />';
if ($rights >= 7) {
    echo $lng['nick'] . ': (' . $lng_profile['nick_lenght'] . ')<br /><input type="text" value="' . $user['name'] . '" name="name" /><br />' .
        $lng['status'] . ': (' . $lng_profile['status_lenght'] . ')<br /><input type="text" value="' . $user['status'] . '" name="status" /><br />';
} else {
    echo '<span class="gray">' . $lng['nick'] . ':</span> <b>' . $user['name'] . '</b><br />' .
        '<span class="gray">' . $lng['status'] . ':</span> ' . $user['status'] . '<br />';
}
echo '</p><p>' . $lng['avatar'] . ':<br />';
$link = '';
if (file_exists(('../files/users/avatar/' . $user['id'] . '.png'))) {
    echo '<img src="' . $homeurl . '/files/users/avatar/' . $user['id'] . '.png" width="32" height="32" alt="' . $user['name'] . '" /><br />';
    $link = ' | <a href="profile.php?act=edit&amp;user=' . $user['id'] . '&amp;delavatar">' . $lng['delete'] . '</a>';
}
echo '<small><a href="profile.php?act=images&amp;mod=avatar&amp;user=' . $user['id'] . '">' . $lng_profile['upload'] . '</a>';
if($user['id'] == $user_id) {
    echo ' | <a href="' . $homeurl . '/pages/faq.php?act=avatars">' . $lng['select'] . '</a>';
}
echo $link . '</small></p>';
echo '<p>' . $lng_profile['photo'] . ':<br />';
$link = '';
if (file_exists('../files/users/photo/' . $user['id'] . '_small.jpg')) {
    echo '<a href="' . $homeurl . '/files/users/photo/' . $user['id'] . '.jpg"><img src="' . $homeurl . '/files/users/photo/' . $user['id'] . '_small.jpg" alt="' . $user['name'] . '" border="0" /></a><br />';
    $link = ' | <a href="profile.php?act=edit&amp;user=' . $user['id'] . '&amp;delphoto">' . $lng['delete'] . '</a>';
}
echo '<small><a href="profile.php?act=images&amp;mod=up_photo&amp;user=' . $user['id'] . '">' . $lng_profile['upload'] . '</a>' . $link . '</small><br />' .
    '</p></div>' .
    '<div class="menu">' .
    '<p><h3><img src="' . $homeurl . '/images/contacts.png" width="16" height="16" class="left" />&#160;' . $lng_profile['personal_data'] . '</h3>' .
    $lng_profile['name'] . ':<br /><input type="text" value="' . _e($user['imname']) . '" name="imname" /></p>' .
    '<p>' . $lng_profile['birth_date'] . '<br />' .
    '<input type="text" value="' . $user['dayb'] . '" size="2" maxlength="2" name="dayb" />.' .
    '<input type="text" value="' . $user['monthb'] . '" size="2" maxlength="2" name="monthb" />.' .
    '<input type="text" value="' . $user['yearofbirth'] . '" size="4" maxlength="4" name="yearofbirth" /></p>' .
    '<p>' . $lng_profile['city'] . ':<br /><input type="text" value="' . $user['live'] . '" name="live" /></p>' .
    '<p>' . $lng_profile['about'] . ':<br /><textarea rows="' . $set_user['field_h'] . '" name="about">' . _e($user['about']) . '</textarea></p>' .
    '<p><h3><img src="' . $homeurl . '/images/mail.png" width="16" height="16" class="left" />&#160;' . $lng_profile['communication'] . '</h3>' .
    $lng_profile['phone_number'] . ':<br /><input type="text" value="' . $user['mibile'] . '" name="mibile" /><br />' .
    '</p><p>E-mail:<br /><small>' . $lng_profile['email_warning'] . '</small><br />' .
    '<input type="text" value="' . $user['mail'] . '" name="mail" /><br />' .
    '<input name="mailvis" type="checkbox" value="1" ' . ($user['mailvis'] ? 'checked="checked"' : '') . ' />&#160;' . $lng_profile['show_in_profile'] . '</p>' .
    '<p>ICQ:<br /><input type="text" value="' . $user['icq'] . '" name="icq" size="10" maxlength="10" /></p>' .
    '<p>Skype:<br /><input type="text" value="' . $user['skype'] . '" name="skype" /></p>' .
    '<p>Jabber:<br /><input type="text" value="' . $user['jabber'] . '" name="jabber" /></p>' .
    '<p>' . $lng_profile['site'] . ':<br /><input type="text" value="' . $user['www'] . '" name="www" /></p>' .
    '</div>';
// Административные функции
if ($rights >= 7) {
    echo '<div class="rmenu"><p><h3><img src="' . $homeurl . '/images/settings.png" width="16" height="16" class="left" />&#160;' . $lng['settings'] . '</h3><ul>';
    if ($rights == 9) {
        echo '<li><input name="karma_off" type="checkbox" value="1" ' . ($user['karma_off'] ? 'checked="checked"' : '') . ' />&#160;<span class="red"><b>' . $lng_profile['deny_karma'] . '</b></span></li>';
    }
    echo '<li><a href="profile.php?act=password&amp;user=' . $user['id'] . '">' . $lng['change_password'] . '</a></li>';
    if ($rights > $user['rights']) {
        echo '<li><a href="profile.php?act=reset&amp;user=' . $user['id'] . '">' . $lng['reset_settings'] . '</a></li>';
    }
    echo '<li>' . $lng_profile['specify_sex'] . ':<br />' .
        '<input type="radio" value="m" name="sex" ' . ($user['sex'] == 'm' ? 'checked="checked"' : '') . '/>&#160;' . $lng_profile['sex_m'] . '<br />' .
        '<input type="radio" value="zh" name="sex" ' . ($user['sex'] == 'zh' ? 'checked="checked"' : '') . '/>&#160;' . $lng_profile['sex_w'] . '</li>' .
        '</ul></p>';
    if ($user['id'] != $user_id) {
        echo '<p><h3><img src="' . $homeurl . '/images/forbidden.png" width="16" height="16" class="left" />&#160;' . $lng_profile['rank'] . '</h3><ul>' .
            '<input type="radio" value="0" name="rights" ' . (!$user['rights'] ? 'checked="checked"' : '') . '/>&#160;<b>' . $lng_profile['rank_0'] . '</b><br />' .
            '<input type="radio" value="3" name="rights" ' . ($user['rights'] == 3 ? 'checked="checked"' : '') . '/>&#160;' . $lng_profile['rank_3'] . '<br />' .
            '<input type="radio" value="4" name="rights" ' . ($user['rights'] == 4 ? 'checked="checked"' : '') . '/>&#160;' . $lng_profile['rank_4'] . '<br />' .
            '<input type="radio" value="5" name="rights" ' . ($user['rights'] == 5 ? 'checked="checked"' : '') . '/>&#160;' . $lng_profile['rank_5'] . '<br />' .
            '<input type="radio" value="6" name="rights" ' . ($user['rights'] == 6 ? 'checked="checked"' : '') . '/>&#160;' . $lng_profile['rank_6'] . '<br />';
        if ($rights == 9) {
            echo '<input type="radio" value="7" name="rights" ' . ($user['rights'] == 7 ? 'checked="checked"' : '') . '/>&#160;' . $lng_profile['rank_7'] . '<br />' .
                '<input type="radio" value="9" name="rights" ' . ($user['rights'] == 9 ? 'checked="checked"' : '') . '/>&#160;<span class="red"><b>' . $lng_profile['rank_9'] . '</b></span><br />';
        }
        echo '</ul></p>';
    }
    echo '</div>';
}
echo '<div class="gmenu"><input type="submit" value="' . $lng['save'] . '" name="submit" /></div>' .
    '</form>' .
    '<div class="phdr"><a href="profile.php?user=' . $user['id'] . '">' . $lng['to_form'] . '</a></div>';
