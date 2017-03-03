<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

if ($systemUser->rights >= 7 && $systemUser->rights > $user['rights']) {
    // Сброс настроек пользователя
    $textl = htmlspecialchars($user['name']) . ': ' . _t('Edit Profile');
    require('../system/head.php');

    $db->query("UPDATE `users` SET `set_user` = '', `set_forum` = '' WHERE `id` = " . $user['id']);

    echo '<div class="gmenu"><p>' . sprintf(_t('For user %s default settings were set.'), $user['name'])
        . '<br />'
        . '<a href="?user=' . $user['id'] . '">' . _t('Profile') . '</a></p></div>';
    require_once('../system/end.php');
}
