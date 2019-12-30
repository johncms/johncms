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

$foundUser = (array) $foundUser;
$textl = htmlspecialchars($foundUser['name']) . ': ' . _t('Edit Profile');

// Проверяем права доступа для редактирования Профиля
if ($foundUser['id'] != $user->id && ($user->rights < 7 || $foundUser['rights'] >= $user->rights)) {
    echo $view->render('system::app/old_content', [
        'title'   => $textl,
        'content' => $tools->displayError(_t('You cannot edit profile of higher administration')),
    ]);
    exit;
}

if (! empty($user->ban)) {
    echo $view->render('system::app/old_content', [
        'title'   => $textl,
        'content' => $tools->displayError(_t('Access forbidden')),
    ]);
    exit;
}

echo '<div class="phdr"><a href="?user=' . $foundUser['id'] . '"><b>' . ($foundUser['id'] != $user->id ? _t('Profile') : _t('My Profile')) . '</b></a> | ' . _t('Edit') . '</div>';

if (isset($_GET['delavatar'])) {
    // Удаляем аватар
    @unlink('../files/users/avatar/' . $foundUser['id'] . '.png');
    echo '<div class="rmenu">' . _t('Avatar is successfully removed') . '</div>';
} elseif (isset($_GET['delphoto'])) {
    // Удаляем фото
    @unlink('../files/users/photo/' . $foundUser['id'] . '.jpg');
    @unlink('../files/users/photo/' . $foundUser['id'] . '_small.jpg');
    echo '<div class="rmenu">' . _t('Photo is successfully removed') . '</div>';
} elseif (isset($_POST['submit'])) {
    // Принимаем данные из формы, проверяем и записываем в базу
    $error = [];
    $foundUser['imname'] = isset($_POST['imname']) ? htmlspecialchars(mb_substr(trim($_POST['imname']), 0, 25)) : '';
    $foundUser['live'] = isset($_POST['live']) ? htmlspecialchars(mb_substr(trim($_POST['live']), 0, 50)) : '';
    $foundUser['dayb'] = isset($_POST['dayb']) ? (int) ($_POST['dayb']) : 0;
    $foundUser['monthb'] = isset($_POST['monthb']) ? (int) ($_POST['monthb']) : 0;
    $foundUser['yearofbirth'] = isset($_POST['yearofbirth']) ? (int) ($_POST['yearofbirth']) : 0;
    $foundUser['about'] = isset($_POST['about']) ? htmlspecialchars(mb_substr(trim($_POST['about']), 0, 500)) : '';
    $foundUser['mibile'] = isset($_POST['mibile']) ? htmlspecialchars(mb_substr(trim($_POST['mibile']), 0, 40)) : '';
    $foundUser['mail'] = isset($_POST['mail']) ? htmlspecialchars(mb_substr(trim($_POST['mail']), 0, 40)) : '';
    $foundUser['mailvis'] = isset($_POST['mailvis']) ? 1 : 0;
    $foundUser['icq'] = isset($_POST['icq']) ? (int) ($_POST['icq']) : 0;
    $foundUser['skype'] = isset($_POST['skype']) ? htmlspecialchars(mb_substr(trim($_POST['skype']), 0, 40)) : '';
    $foundUser['jabber'] = isset($_POST['jabber']) ? htmlspecialchars(mb_substr(trim($_POST['jabber']), 0, 40)) : '';
    $foundUser['www'] = isset($_POST['www']) ? htmlspecialchars(mb_substr(trim($_POST['www']), 0, 40)) : '';
    // Данные юзера (для Администраторов)
    $foundUser['name'] = isset($_POST['name']) ? htmlspecialchars(mb_substr(trim($_POST['name']), 0, 20)) : $foundUser['name'];
    $foundUser['status'] = isset($_POST['status']) ? htmlspecialchars(mb_substr(trim($_POST['status']), 0, 50)) : '';
    $foundUser['karma_off'] = isset($_POST['karma_off']) ? 1 : 0;
    $foundUser['sex'] = isset($_POST['sex']) && $_POST['sex'] == 'm' ? 'm' : 'zh';
    $foundUser['rights'] = isset($_POST['rights']) ? abs((int) ($_POST['rights'])) : $foundUser['rights'];

    // Проводим необходимые проверки
    if ($foundUser['rights'] > $user->rights || $foundUser['rights'] > 9 || $foundUser['rights'] < 0) {
        $foundUser['rights'] = 0;
    }

    if ($user->rights >= 7) {
        if (mb_strlen($foundUser['name']) < 2 || mb_strlen($foundUser['name']) > 20) {
            $error[] = _t('Min. nick length 2, max. 20 characters');
        }

        $lat_nick = $tools->rusLat($foundUser['name']);

        if (preg_match("/[^0-9a-z\-\@\*\(\)\?\!\~\_\=\[\]]+/", $lat_nick)) {
            $error[] = _t('Nick contains invalid characters');
        }
    }
    if ($foundUser['dayb'] || $foundUser['monthb'] || $foundUser['yearofbirth']) {
        if ($foundUser['dayb'] < 1 || $foundUser['dayb'] > 31 || $foundUser['monthb'] < 1 || $foundUser['monthb'] > 12) {
            $error[] = _t('Invalid format date of birth');
        }
    }

    if ($foundUser['icq'] && ($foundUser['icq'] < 10000 || $foundUser['icq'] > 999999999)) {
        $error[] = _t('ICQ number must be at least 5 digits and max. 10');
    }

    if (! $error) {
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
            $foundUser['imname'],
            $foundUser['live'],
            $foundUser['dayb'],
            $foundUser['monthb'],
            $foundUser['yearofbirth'],
            $foundUser['about'],
            $foundUser['mibile'],
            $foundUser['mail'],
            $foundUser['mailvis'],
            $foundUser['icq'],
            $foundUser['skype'],
            $foundUser['jabber'],
            $foundUser['www'],
            $foundUser['id'],
        ]);

        if ($user->rights >= 7) {
            $stmt = $db->prepare('UPDATE `users` SET
              `name` = ?,
              `status` = ?,
              `karma_off` = ?,
              `sex` = ?,
              `rights` = ?
              WHERE `id` = ?
            ');

            $stmt->execute([
                $foundUser['name'],
                $foundUser['status'],
                $foundUser['karma_off'],
                $foundUser['sex'],
                $foundUser['rights'],
                $foundUser['id'],
            ]);
        }

        echo '<div class="gmenu">' . _t('Data saved') . '</div>';
    } else {
        echo $tools->displayError($error);
    }

    header('Location: ?act=edit&user=' . $foundUser['id']);
    exit;
}

// Форма редактирования анкеты пользователя
echo '<form action="?act=edit&amp;user=' . $foundUser['id'] . '" method="post">' .
    '<div class="gmenu"><p>' .
    _t('Username') . ': <b>' . $foundUser['name_lat'] . '</b><br>';

if ($user->rights >= 7) {
    echo _t('Nickname') . ': (' . _t('Min.2, Max. 20') . ')<br><input type="text" value="' . $foundUser['name'] . '" name="name" /><br>' .
        _t('Status') . ': (' . _t('Max. 50') . ')<br><input type="text" value="' . $foundUser['status'] . '" name="status" /><br>';
} else {
    echo '<span class="gray">' . _t('Nickname') . ':</span> <b>' . $foundUser['name'] . '</b><br>' .
        '<span class="gray">' . _t('Status') . ':</span> ' . $foundUser['status'] . '<br>';
}

echo '</p><p>' . _t('Avatar') . ':<br>';
$link = '';

if (file_exists(('../files/users/avatar/' . $foundUser['id'] . '.png'))) {
    echo '<img src="../files/users/avatar/' . $foundUser['id'] . '.png" width="32" height="32" alt="' . $foundUser['name'] . '" /><br>';
    $link = ' | <a href="?act=edit&amp;user=' . $foundUser['id'] . '&amp;delavatar">' . _t('Delete') . '</a>';
}

echo '<small><a href="?act=images&amp;mod=avatar&amp;user=' . $foundUser['id'] . '">' . _t('Upload') . '</a>';

if ($foundUser['id'] == $user->id) {
    echo ' | <a href="../help/?act=avatars">' . _t('Select in Catalog') . '</a>';
}

echo $link . '</small></p>';
echo '<p>' . _t('Photo') . ':<br>';
$link = '';

if (file_exists('../files/users/photo/' . $foundUser['id'] . '_small.jpg')) {
    echo '<a href="../files/users/photo/' . $foundUser['id'] . '.jpg"><img src="../files/users/photo/' . $foundUser['id'] . '_small.jpg" alt="' . $foundUser['name'] . '" border="0" /></a><br>';
    $link = ' | <a href="?act=edit&amp;user=' . $foundUser['id'] . '&amp;delphoto">' . _t('Delete') . '</a>';
}

echo '<small><a href="?act=images&amp;mod=up_photo&amp;user=' . $foundUser['id'] . '">' . _t('Upload') . '</a>' . $link . '</small><br>' .
    '</p></div>' .
    '<div class="menu">' .
    '<p><h3><img src="../images/contacts.png" width="16" height="16" class="left" />&#160;' . _t('Personal info') . '</h3>' .
    _t('Your name') . ':<br><input type="text" value="' . $foundUser['imname'] . '" name="imname" /></p>' .
    '<p>' . _t('Date of birth (d.m.y)') . '<br>' .
    '<input type="text" value="' . $foundUser['dayb'] . '" size="2" maxlength="2" name="dayb" />.' .
    '<input type="text" value="' . $foundUser['monthb'] . '" size="2" maxlength="2" name="monthb" />.' .
    '<input type="text" value="' . $foundUser['yearofbirth'] . '" size="4" maxlength="4" name="yearofbirth" /></p>' .
    '<p>' . _t('City, Country') . ':<br><input type="text" value="' . $foundUser['live'] . '" name="live" /></p>' .
    '<p>' . _t('About myself') . ':<br><textarea rows="' . $user->config->fieldHeight . '" name="about">' . strip_tags($foundUser['about']) . '</textarea></p>' .
    '<p><h3><img src="../images/mail.png" width="16" height="16" class="left" />&#160;' . _t('Contacts') . '</h3>' .
    _t('Phone number') . ':<br><input type="text" value="' . $foundUser['mibile'] . '" name="mibile" /><br>' .
    '</p><p>E-mail<br>' .
    '<input type="text" value="' . $foundUser['mail'] . '" name="mail" /><br>' .
    '<input name="mailvis" type="checkbox" value="1" ' . ($foundUser['mailvis'] ? 'checked="checked"' : '') . ' />&#160;' . _t('Show in Profile') . '<br>' .
    '<small class="gray">' . _t('Warning! Write your e-mail correctly. Your password will be sent to the email address on record for this account.') . '</small></p>' .
    '<p>ICQ:<br><input type="text" value="' . $foundUser['icq'] . '" name="icq" size="10" maxlength="10" /></p>' .
    '<p>Skype:<br><input type="text" value="' . $foundUser['skype'] . '" name="skype" /></p>' .
    '<p>Jabber:<br><input type="text" value="' . $foundUser['jabber'] . '" name="jabber" /></p>' .
    '<p>' . _t('Site') . ':<br><input type="text" value="' . $foundUser['www'] . '" name="www" /></p>' .
    '</div>';

// Административные функции
if ($user->rights >= 7) {
    echo '<div class="rmenu"><p><h3><img src="../images/settings.png" width="16" height="16" class="left" />&#160;' . _t('Administrative Functions') . '</h3><ul>';
    echo '<li><input name="karma_off" type="checkbox" value="1" ' . ($foundUser['karma_off'] ? 'checked="checked"' : '') . ' />&#160;' . _t('Prohibit Karma') . '</li>';
    echo '<li><a href="?act=password&amp;user=' . $foundUser['id'] . '">' . _t('Change Password') . '</a></li>';

    if ($user->rights > $foundUser['rights']) {
        echo '<li><a href="?act=reset&amp;user=' . $foundUser['id'] . '">' . _t('Reset User options to default') . '</a></li>';
    }

    echo '<li>' . _t('Select gender') . ':<br>' .
        '<input type="radio" value="m" name="sex" ' . ($foundUser['sex'] == 'm' ? 'checked="checked"' : '') . '/>&#160;' . _t('Man') . '<br>' .
        '<input type="radio" value="zh" name="sex" ' . ($foundUser['sex'] == 'zh' ? 'checked="checked"' : '') . '/>&#160;' . _t('Woman') . '</li>' .
        '</ul></p>';

    if ($foundUser['id'] != $user->id) {
        echo '<p><h3><img src="../images/forbidden.png" width="16" height="16" class="left" />&#160;' . _t('Position on the Site') . '</h3><ul>' .
            '<input type="radio" value="0" name="rights" ' . (! $foundUser['rights'] ? 'checked="checked"' : '') . '/>&#160;<b>' . _t('User') . '</b><br>' .
            '<input type="radio" value="3" name="rights" ' . ($foundUser['rights'] == 3 ? 'checked="checked"' : '') . '/>&#160;' . _t('Forum Moderator') . '<br>' .
            '<input type="radio" value="4" name="rights" ' . ($foundUser['rights'] == 4 ? 'checked="checked"' : '') . '/>&#160;' . _t('Download Moderator') . '<br>' .
            '<input type="radio" value="5" name="rights" ' . ($foundUser['rights'] == 5 ? 'checked="checked"' : '') . '/>&#160;' . _t('Library Moderator') . '<br>' .
            '<input type="radio" value="6" name="rights" ' . ($foundUser['rights'] == 6 ? 'checked="checked"' : '') . '/>&#160;' . _t('Super Modererator') . '<br>';

        if ($user->rights == 9) {
            echo '<input type="radio" value="7" name="rights" ' . ($foundUser['rights'] == 7 ? 'checked="checked"' : '') . '/>&#160;' . _t('Administrator') . '<br>' .
                '<input type="radio" value="9" name="rights" ' . ($foundUser['rights'] == 9 ? 'checked="checked"' : '') . '/>&#160;<span class="red"><b>' . _t('Supervisor') . '</b></span><br>';
        }
        echo '</ul></p>';
    }
    echo '</div>';
}

echo '<div class="gmenu"><input type="submit" value="' . _t('Save') . '" name="submit" /></div></form>';
