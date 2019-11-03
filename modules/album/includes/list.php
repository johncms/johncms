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

// Список альбомов юзера
if (isset($_SESSION['ap'])) {
    unset($_SESSION['ap']);
}

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $user */
$user = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

echo '<div class="phdr"><a href="./"><b>' . _t('Photo Albums') . '</b></a> | ' . _t('Personal') . '</div>';
$req = $db->query("SELECT * FROM `cms_album_cat` WHERE `user_id` = '" . $foundUser['id'] . "' " . ($foundUser['id'] == $user->id || $user->rights >= 6 ? '' : 'AND `access` > 1') . ' ORDER BY `sort` ASC');
$total = $req->rowCount();

if ($foundUser['id'] == $user->id && $total < $max_album && empty($user->ban) || $user->rights >= 7) {
    echo '<div class="topmenu"><a href="?act=edit&amp;user=' . $foundUser['id'] . '">' . _t('Create Album') . '</a></div>';
}

echo '<div class="user"><p>' . $tools->displayUser($foundUser, ['iphide' => 1]) . '</p></div>';

if ($total) {
    $i = 0;
    while ($res = $req->fetch()) {
        $count = $db->query("SELECT COUNT(*) FROM `cms_album_files` WHERE `album_id` = '" . $res['id'] . "'")->fetchColumn();
        echo($i % 2 ? '<div class="list2">' : '<div class="list1">') .
            '<img src="../images/album-' . $res['access'] . '.gif" width="16" height="16" class="left" />&#160;' .
            '<a href="?act=show&amp;al=' . $res['id'] . '&amp;user=' . $foundUser['id'] . '"><b>' . $tools->checkout($res['name']) . '</b></a>&#160;(' . $count . ')';

        if ($foundUser['id'] == $user->id || $user->rights >= 6 || ! empty($res['description'])) {
            $menu = [
                '<a href="?act=sort&amp;mod=up&amp;al=' . $res['id'] . '&amp;user=' . $foundUser['id'] . '">' . _t('Up') . '</a>',
                '<a href="?act=sort&amp;mod=down&amp;al=' . $res['id'] . '&amp;user=' . $foundUser['id'] . '">' . _t('Down') . '</a>',
                '<a href="?act=edit&amp;al=' . $res['id'] . '&amp;user=' . $foundUser['id'] . '">' . _t('Edit') . '</a>',
                '<a href="?act=delete&amp;al=' . $res['id'] . '&amp;user=' . $foundUser['id'] . '">' . _t('Delete') . '</a>',
            ];
            echo '<div class="sub">' .
                (! empty($res['description']) ? '<div class="gray">' . $tools->checkout($res['description'], 1, 1) . '</div>' : '') .
                ($foundUser['id'] == $user->id && empty($user->ban) || $user->rights >= 6 ? implode(' | ', $menu) : '') .
                '</div>';
        }

        echo '</div>';
        ++$i;
    }
} else {
    echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
}

echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';
