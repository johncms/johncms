<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

$textl = htmlspecialchars($user['name']) . ': ' . _td('Edit Profile');
require('../incfiles/head.php');

// Проверяем права доступа для редактирования Профиля
if ($user['id'] != $user_id && ($rights < 7 || $user['rights'] > $rights)) {
    echo functions::display_error(_td('You cannot edit profile of higher administration'));
    require('../incfiles/end.php');
    exit;
}

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);

// Сброс настроек
if ($rights >= 7 && $rights > $user['rights'] && $act == 'reset') {
    $db->exec("UPDATE `users` SET `set_user` = '', `set_forum` = '' WHERE `id` = " . $user['id']);
    echo '<div class="gmenu"><p>' . _td('Default settings are set') . '<br /><a href="?user=' . $user['id'] . '">' . _td('Back') . '</a></p></div>';
    require('../incfiles/end.php');
    exit;
}

echo '<div class="phdr"><a href="?user=' . $user['id'] . '"><b>' . ($user['id'] != $user_id ? _td('Profile') : _td('My Profile')) . '</b></a> | ' . _td('Edit') . '</div>';

if (isset($_GET['delavatar'])) {
    // Удаляем аватар
    @unlink('../files/users/avatar/' . $user['id'] . '.png');
    echo '<div class="rmenu">' . _td('Avatar is successfully removed') . '</div>';
} elseif (isset($_GET['delphoto'])) {
    // Удаляем фото
    @unlink('../files/users/photo/' . $user['id'] . '.jpg');
    @unlink('../files/users/photo/' . $user['id'] . '_small.jpg');
    echo '<div class="rmenu">' . _td('Photo is successfully removed') . '</div>';
} elseif (isset($_POST['submit'])) {
    // Принимаем данные из формы, проверяем и записываем в базу
    $error = [];
    $user['imname'] = isset($_POST['imname']) ? functions::check(mb_substr($_POST['imname'], 0, 25)) : '';
    $user['live'] = isset($_POST['live']) ? functions::check(mb_substr($_POST['live'], 0, 50)) : '';
    $user['dayb'] = isset($_POST['dayb']) ? intval($_POST['dayb']) : 0;
    $user['monthb'] = isset($_POST['monthb']) ? intval($_POST['monthb']) : 0;
    $user['yearofbirth'] = isset($_POST['yearofbirth']) ? intval($_POST['yearofbirth']) : 0;
    $user['about'] = isset($_POST['about']) ? functions::check(mb_substr($_POST['about'], 0, 500)) : '';
    $user['mibile'] = isset($_POST['mibile']) ? functions::check(mb_substr($_POST['mibile'], 0, 40)) : '';
    $user['mail'] = isset($_POST['mail']) ? functions::check(mb_substr($_POST['mail'], 0, 40)) : '';
    $user['mailvis'] = isset($_POST['mailvis']) ? 1 : 0;
    $user['icq'] = isset($_POST['icq']) ? intval($_POST['icq']) : 0;
    $user['skype'] = isset($_POST['skype']) ? functions::check(mb_substr($_POST['skype'], 0, 40)) : '';
    $user['jabber'] = isset($_POST['jabber']) ? functions::check(mb_substr($_POST['jabber'], 0, 40)) : '';
    $user['www'] = isset($_POST['www']) ? functions::check(mb_substr($_POST['www'], 0, 40)) : '';
    // Данные юзера (для Администраторов)
    $user['name'] = isset($_POST['name']) ? functions::check(mb_substr($_POST['name'], 0, 20)) : $user['name'];
    $user['status'] = isset($_POST['status']) ? functions::check(mb_substr($_POST['status'], 0, 50)) : '';
    $user['karma_off'] = isset($_POST['karma_off']) ? 1 : 0;
    $user['sex'] = isset($_POST['sex']) && $_POST['sex'] == 'm' ? 'm' : 'zh';
    $user['rights'] = isset($_POST['rights']) ? abs(intval($_POST['rights'])) : $user['rights'];

    // Проводим необходимые проверки
    if ($user['rights'] > $rights || $user['rights'] > 9 || $user['rights'] < 0) {
        $user['rights'] = 0;
    }

    if ($rights >= 7) {
        if (mb_strlen($user['name']) < 2 || mb_strlen($user['name']) > 20) {
            $error[] = _td('Min. nick length 2, max. 20 characters');
        }

        $lat_nick = functions::rus_lat(mb_strtolower($user['name']));

        if (preg_match("/[^0-9a-z\-\@\*\(\)\?\!\~\_\=\[\]]+/", $lat_nick)) {
            $error[] = _td('Nick contains invalid characters');
        }
    }
    if ($user['dayb'] || $user['monthb'] || $user['yearofbirth']) {
        if ($user['dayb'] < 1 || $user['dayb'] > 31 || $user['monthb'] < 1 || $user['monthb'] > 12) {
            $error[] = _td('Invalid format date of birth');
        }
    }

    if ($user['icq'] && ($user['icq'] < 10000 || $user['icq'] > 999999999)) {
        $error[] = _td('ICQ number must be at least 5 digits and max. 10');
    }

    if (!$error) {
        $stmt = $db->prepare('UPDATE `users` SET
          `imname` = ?,
          `live` = ?,
          `dayb` = ?,
          `monthb` = ?,
          `yearofbirth` = ?,
          `about` = ?,
          `mibile` = ?,
          `mail` = ?,
          `mailvis` = ?,
          `icq` = ?,
          `skype` = ?,
          `jabber` = ?,
          `www` = ?
          WHERE `id` = ?
        ');

        $stmt->execute([
            $user['imname'],
            $user['live'],
            $user['dayb'],
            $user['monthb'],
            $user['yearofbirth'],
            $user['about'],
            $user['mibile'],
            $user['mail'],
            $user['mailvis'],
            $user['icq'],
            $user['skype'],
            $user['jabber'],
            $user['www'],
            $user['id'],
        ]);

        if ($rights >= 7) {
            $stmt = $db->prepare('UPDATE `users` SET
              `name` = ?,
              `status` = ?,
              `karma_off` = ?,
              `sex` = ?,
              `rights` = ?
              WHERE `id` = ?
            ');

            $stmt->execute([
                $user['name'],
                $user['status'],
                $user['karma_off'],
                $user['sex'],
                $user['rights'],
                $user['id'],
            ]);
        }

        echo '<div class="gmenu">' . _td('Data saved') . '</div>';
    } else {
        echo functions::display_error($error);
    }

    header('Location: ?act=edit&user=' . $user['id']);
    exit;
}

// Форма редактирования анкеты пользователя
echo '<form action="?act=edit&amp;user=' . $user['id'] . '" method="post">' .
    '<div class="gmenu"><p>' .
    _td('Username') . ': <b>' . $user['name_lat'] . '</b><br />';

if ($rights >= 7) {
    echo _td('Nickname') . ': (' . _td('Min.2, Max. 20') . ')<br /><input type="text" value="' . $user['name'] . '" name="name" /><br />' .
        _td('Status') . ': (' . _td('Max. 50') . ')<br /><input type="text" value="' . $user['status'] . '" name="status" /><br />';
} else {
    echo '<span class="gray">' . _td('Nickname') . ':</span> <b>' . $user['name'] . '</b><br />' .
        '<span class="gray">' . _td('Status') . ':</span> ' . $user['status'] . '<br />';
}

echo '</p><p>' . _td('Avatar') . ':<br />';
$link = '';

if (file_exists(('../files/users/avatar/' . $user['id'] . '.png'))) {
    echo '<img src="../files/users/avatar/' . $user['id'] . '.png" width="32" height="32" alt="' . $user['name'] . '" /><br />';
    $link = ' | <a href="?act=edit&amp;user=' . $user['id'] . '&amp;delavatar">' . _td('Delete') . '</a>';
}

echo '<small><a href="?act=images&amp;mod=avatar&amp;user=' . $user['id'] . '">' . _td('Upload') . '</a>';

if ($user['id'] == $user_id) {
    echo ' | <a href="../help/?act=avatars">' . _td('Select in Catalog') . '</a>';
}

echo $link . '</small></p>';
echo '<p>' . _td('Photo') . ':<br />';
$link = '';

if (file_exists('../files/users/photo/' . $user['id'] . '_small.jpg')) {
    echo '<a href="../files/users/photo/' . $user['id'] . '.jpg"><img src="../files/users/photo/' . $user['id'] . '_small.jpg" alt="' . $user['name'] . '" border="0" /></a><br />';
    $link = ' | <a href="?act=edit&amp;user=' . $user['id'] . '&amp;delphoto">' . _td('Delete') . '</a>';
}

echo '<small><a href="?act=images&amp;mod=up_photo&amp;user=' . $user['id'] . '">' . _td('Upload') . '</a>' . $link . '</small><br />' .
    '</p></div>' .
    '<div class="menu">' .
    '<p><h3><img src="../images/contacts.png" width="16" height="16" class="left" />&#160;' . _td('Personal info') . '</h3>' .
    _td('Your name') . ':<br /><input type="text" value="' . $user['imname'] . '" name="imname" /></p>' .
    '<p>' . _td('Date of birth (d.m.y)') . '<br />' .
    '<input type="text" value="' . $user['dayb'] . '" size="2" maxlength="2" name="dayb" />.' .
    '<input type="text" value="' . $user['monthb'] . '" size="2" maxlength="2" name="monthb" />.' .
    '<input type="text" value="' . $user['yearofbirth'] . '" size="4" maxlength="4" name="yearofbirth" /></p>' .
    '<p>' . _td('City, Country') . ':<br /><input type="text" value="' . $user['live'] . '" name="live" /></p>' .
    '<p>' . _td('About myself') . ':<br /><textarea rows="' . $set_user['field_h'] . '" name="about">' . strip_tags($user['about']) . '</textarea></p>' .
    '<p><h3><img src="../images/mail.png" width="16" height="16" class="left" />&#160;' . _td('Contacts') . '</h3>' .
    _td('Phone number') . ':<br /><input type="text" value="' . $user['mibile'] . '" name="mibile" /><br />' .
    '</p><p>E-mail:<br /><small>' . _td('Warning! Write your e-mail correctly. Your password will be sent to the email address on record for this account.') . '</small><br />' .
    '<input type="text" value="' . $user['mail'] . '" name="mail" /><br />' .
    '<input name="mailvis" type="checkbox" value="1" ' . ($user['mailvis'] ? 'checked="checked"' : '') . ' />&#160;' . _td('Show in Profile') . '</p>' .
    '<p>ICQ:<br /><input type="text" value="' . $user['icq'] . '" name="icq" size="10" maxlength="10" /></p>' .
    '<p>Skype:<br /><input type="text" value="' . $user['skype'] . '" name="skype" /></p>' .
    '<p>Jabber:<br /><input type="text" value="' . $user['jabber'] . '" name="jabber" /></p>' .
    '<p>' . _td('Site') . ':<br /><input type="text" value="' . $user['www'] . '" name="www" /></p>' .
    '</div>';

// Административные функции
if ($rights >= 7) {
    echo '<div class="rmenu"><p><h3><img src="../images/settings.png" width="16" height="16" class="left" />&#160;' . _td('Administrative Functions') . '</h3><ul>';
    echo '<li><input name="karma_off" type="checkbox" value="1" ' . ($user['karma_off'] ? 'checked="checked"' : '') . ' />&#160;' . _td('Prohibit Karma') . '</li>';
    echo '<li><a href="?act=password&amp;user=' . $user['id'] . '">' . _td('Change Password') . '</a></li>';

    if ($rights > $user['rights']) {
        echo '<li><a href="?act=reset&amp;user=' . $user['id'] . '">' . _td('Reset User options to default') . '</a></li>';
    }

    echo '<li>' . _td('Select gender') . ':<br />' .
        '<input type="radio" value="m" name="sex" ' . ($user['sex'] == 'm' ? 'checked="checked"' : '') . '/>&#160;' . _td('Man') . '<br />' .
        '<input type="radio" value="zh" name="sex" ' . ($user['sex'] == 'zh' ? 'checked="checked"' : '') . '/>&#160;' . _td('Woman') . '</li>' .
        '</ul></p>';

    if ($user['id'] != $user_id) {
        echo '<p><h3><img src="../images/forbidden.png" width="16" height="16" class="left" />&#160;' . _td('Position on the Site') . '</h3><ul>' .
            '<input type="radio" value="0" name="rights" ' . (!$user['rights'] ? 'checked="checked"' : '') . '/>&#160;<b>' . _td('User') . '</b><br />' .
            '<input type="radio" value="3" name="rights" ' . ($user['rights'] == 3 ? 'checked="checked"' : '') . '/>&#160;' . _td('Forum Moderator') . '<br />' .
            '<input type="radio" value="4" name="rights" ' . ($user['rights'] == 4 ? 'checked="checked"' : '') . '/>&#160;' . _td('Download Moderator') . '<br />' .
            '<input type="radio" value="5" name="rights" ' . ($user['rights'] == 5 ? 'checked="checked"' : '') . '/>&#160;' . _td('Library Moderator') . '<br />' .
            '<input type="radio" value="6" name="rights" ' . ($user['rights'] == 6 ? 'checked="checked"' : '') . '/>&#160;' . _td('Super Modererator') . '<br />';
        if ($rights == 9) {
            echo '<input type="radio" value="7" name="rights" ' . ($user['rights'] == 7 ? 'checked="checked"' : '') . '/>&#160;' . _td('Administrator') . '<br />' .
                '<input type="radio" value="9" name="rights" ' . ($user['rights'] == 9 ? 'checked="checked"' : '') . '/>&#160;<span class="red"><b>' . _td('Supervisor') . '</b></span><br />';
        }
        echo '</ul></p>';
    }
    echo '</div>';
}

echo '<div class="gmenu"><input type="submit" value="' . _td('Save') . '" name="submit" /></div>' .
    '</form>';
