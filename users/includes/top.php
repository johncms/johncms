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

$headmod = 'userstop';
$textl = _t('Top Activity');
require('../system/head.php');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

// Функция отображения списков
function get_top($order = 'postforum')
{
    global $db, $tools;
    $req = $db->query("SELECT * FROM `users` WHERE `$order` > 0 ORDER BY `$order` DESC LIMIT 9");

    if ($req->rowCount()) {
        $out = '';
        $i = 0;

        while ($res = $req->fetch()) {
            $out .= $i % 2 ? '<div class="list2">' : '<div class="list1">';
            $out .= $tools->displayUser($res, ['header' => ('<b>' . $res[$order]) . '</b>']) . '</div>';
            ++$i;
        }

        return $out;
    } else {
        return '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
    }
}

// Меню выбора
$menu = [
    (!$mod ? '<b>' . _t('Forum') . '</b>' : '<a href="index.php?act=top">' . _t('Forum') . '</a>'),
    ($mod == 'guest' ? '<b>' . _t('Guestbook') . '</b>' : '<a href="index.php?act=top&amp;mod=guest">' . _t('Guestbook') . '</a>'),
    ($mod == 'comm' ? '<b>' . _t('Comments') . '</b>' : '<a href="index.php?act=top&amp;mod=comm">' . _t('Comments') . '</a>'),
];

if ($set_karma['on']) {
    $menu[] = $mod == 'karma' ? '<b>' . _t('Karma') . '</b>' : '<a href="index.php?act=top&amp;mod=karma">' . _t('Karma') . '</a>';
}

switch ($mod) {
    case 'guest':
        // Топ Гостевой
        echo '<div class="phdr"><a href="index.php"><b>' . _t('Community') . '</b></a> | ' . _t('Most active in Guestbook') . '</div>';
        echo '<div class="topmenu">' . implode(' | ', $menu) . '</div>';
        echo get_top('postguest');
        echo '<div class="phdr"><a href="../guestbook/index.php">' . _t('Guestbook') . '</a></div>';
        break;

    case 'comm':
        // Топ комментариев
        echo '<div class="phdr"><a href="index.php"><b>' . _t('Community') . '</b></a> | ' . _t('Most commentators') . '</div>';
        echo '<div class="topmenu">' . implode(' | ', $menu) . '</div>';
        echo get_top('komm');
        echo '<div class="phdr"><a href="../index.php">' . _t('Home') . '</a></div>';
        break;

    case 'karma':
        // Топ Кармы
        if ($set_karma['on']) {
            echo '<div class="phdr"><a href="index.php"><b>' . _t('Community') . '</b></a> | ' . _t('Best Karma') . '</div>';
            echo '<div class="topmenu">' . implode(' | ', $menu) . '</div>';
            $req = $db->query("SELECT *, (`karma_plus` - `karma_minus`) AS `karma` FROM `users` WHERE (`karma_plus` - `karma_minus`) > 0 ORDER BY `karma` DESC LIMIT 9");

            if ($req->rowCount()) {
                while ($res = $req->fetch()) {
                    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                    echo $tools->displayUser($res, ['header' => ('<b>' . $res['karma']) . '</b>']) . '</div>';
                    ++$i;
                }
            } else {
                echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
            }

            echo '<div class="phdr"><a href="../index.php">' . _t('Home') . '</a></div>';
        }
        break;

    default:
        // Топ Форума
        echo '<div class="phdr"><a href="index.php"><b>' . _t('Community') . '</b></a> | ' . _t('Most active in Forum') . '</div>';
        echo '<div class="topmenu">' . implode(' | ', $menu) . '</div>';
        echo get_top('postforum');
        echo '<div class="phdr"><a href="../forum/index.php">' . _t('Forum') . '</a></div>';
}

echo '<p><a href="index.php">' . _t('Back') . '</a></p>';
