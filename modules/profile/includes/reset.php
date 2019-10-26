<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

if ($systemUser->rights >= 7 && $systemUser->rights > $user['rights']) {
    // Сброс настроек пользователя
    $textl = htmlspecialchars($user['name']) . ': ' . _t('Edit Profile');
    require 'system/head.php';

    $db->query("UPDATE `users` SET `set_user` = '', `set_forum` = '' WHERE `id` = " . $user['id']);

    echo '<div class="gmenu"><p>' . sprintf(_t('For user %s default settings were set.'), $user['name'])
        . '<br />'
        . '<a href="?user=' . $user['id'] . '">' . _t('Profile') . '</a></p></div>';
    require_once 'system/end.php';
}
