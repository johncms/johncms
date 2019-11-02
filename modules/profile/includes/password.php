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

$textl = htmlspecialchars($user['name']) . ': ' . _t('Change Password');

// Проверяем права доступа
if ($user['id'] != $systemUser->id && ($systemUser->rights < 7 || $user['rights'] > $systemUser->rights)) {
    echo $view->render('system::app/old_content', [
        'title'   => $textl,
        'content' => $tools->displayError(_t('Access forbidden')),
    ]);
    exit;
}

switch ($mod) {
    case 'change':
        // Меняем пароль
        $error = [];
        $oldpass = isset($_POST['oldpass']) ? trim($_POST['oldpass']) : '';
        $newpass = isset($_POST['newpass']) ? trim($_POST['newpass']) : '';
        $newconf = isset($_POST['newconf']) ? trim($_POST['newconf']) : '';
        $autologin = isset($_POST['autologin']) ? 1 : 0;

        if ($user['id'] != $systemUser->id) {
            if (! $newpass || ! $newconf) {
                $error[] = _t('It is necessary to fill in all fields');
            }
        } else {
            if (! $oldpass || ! $newpass || ! $newconf) {
                $error[] = _t('It is necessary to fill in all fields');
            }
        }

        if (! $error && $user['id'] == $systemUser->id && md5(md5($oldpass)) !== $user['password']) {
            $error[] = _t('Old password entered incorrectly');
        }

        if ($newpass != $newconf) {
            $error[] = _t('The password confirmation you entered is wrong');
        }

        if (! $error && (strlen($newpass) < 3)) {
            $error[] = _t('The password must contain at least 3 characters');
        }

        if (! $error) {
            // Записываем в базу
            $db->prepare('UPDATE `users` SET `password` = ? WHERE `id` = ?')->execute([
                md5(md5($newpass)),
                $user['id'],
            ]);

            // Проверяем и записываем COOKIES
            if (isset($_COOKIE['cuid'], $_COOKIE['cups'])) {
                setcookie('cups', md5($newpass), time() + 3600 * 24 * 365);
            }

            echo '<div class="gmenu"><p><b>' . _t('Password successfully changed') . '</b><br />' .
                '<a href="' . ($systemUser->id == $user['id'] ? '../login.php' : '?user=' . $user['id']) . '">' . _t('Continue') . '</a></p>';
            echo '</div>';
        } else {
            echo $tools->displayError($error,
                '<a href="?act=password&amp;user=' . $user['id'] . '">' . _t('Repeat') . '</a>');
        }
        break;

    default:
        // Форма смены пароля
        echo '<div class="phdr"><b>' . _t('Change Password') . ':</b> ' . $user['name'] . '</div>';
        echo '<form action="?act=password&amp;mod=change&amp;user=' . $user['id'] . '" method="post">';

        if ($user['id'] == $systemUser->id) {
            echo '<div class="menu"><p>' . _t('Enter old password') . ':<br /><input type="password" name="oldpass" /></p></div>';
        }

        echo '<div class="gmenu"><p>' . _t('Enter new password') . ':<br />' .
            '<input type="password" name="newpass" /><br />' . _t('Repeat password') . ':<br />' .
            '<input type="password" name="newconf" /></p>' .
            '<p><input type="submit" value="' . _t('Save') . '" name="submit" />' .
            '</p></div></form>' .
            '<p><a href="?user=' . $user['id'] . '">' . _t('Profile') . '</a></p>';
}
