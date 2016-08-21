<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

if ($rights >= 7 && $rights > $user['rights']) {
    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);

    // Сброс настроек пользователя
    $textl = htmlspecialchars($user['name']) . ': ' . $lng_profile['profile_edit'];
    require('../incfiles/head.php');

    $db->query("UPDATE `users` SET `set_user` = '', `set_forum` = '' WHERE `id` = " . $user['id']);

    echo '<div class="gmenu"><p>' . $lng_profile['reset1'] . ' <b>' . $user['name'] . '</b> ' . $lng_profile['reset2'] . '<br />' .
        '<a href="?user=' . $user['id'] . '">' . $lng['profile'] . '</a></p></div>';
    require_once('../incfiles/end.php');
    exit;
}
