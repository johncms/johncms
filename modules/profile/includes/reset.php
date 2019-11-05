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

if ($user->rights >= 7 && $user->rights > $foundUser['rights']) {
    // Сброс настроек пользователя
    $textl = htmlspecialchars($foundUser['name']) . ': ' . _t('Edit Profile');
    $db->query("UPDATE `users` SET `set_user` = '', `set_forum` = '' WHERE `id` = " . $foundUser['id']);

    echo '<div class="gmenu"><p>' . sprintf(_t('For user %s default settings were set.'), $foundUser['name'])
        . '<br />'
        . '<a href="?user=' . $foundUser['id'] . '">' . _t('Profile') . '</a></p></div>';
}
