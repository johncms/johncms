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

ob_start();

// Функция отображения списков
function get_top($order = 'postforum')
{
    global $db, $tools;
    $req = $db->query("SELECT * FROM `users` WHERE `${order}` > 0 ORDER BY `${order}` DESC LIMIT 9");

    if ($req->rowCount()) {
        $out = '';
        $i = 0;

        while ($res = $req->fetch()) {
            $out .= $i % 2 ? '<div class="list2">' : '<div class="list1">';
            $out .= $tools->displayUser($res, ['header' => ('<b>' . $res[$order]) . '</b>']) . '</div>';
            ++$i;
        }

        return $out;
    }

    return '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
}

// Меню выбора
$menu = [
    (! $mod ? '<b>' . _t('Forum') . '</b>' : '<a href="?act=top">' . _t('Forum') . '</a>'),
    ($mod == 'guest' ? '<b>' . _t('Guestbook') . '</b>' : '<a href="?act=top&amp;mod=guest">' . _t('Guestbook') . '</a>'),
    ($mod == 'comm' ? '<b>' . _t('Comments') . '</b>' : '<a href="?act=top&amp;mod=comm">' . _t('Comments') . '</a>'),
];

if ($config->karma) {
    $menu[] = $mod == 'karma' ? '<b>' . _t('Karma') . '</b>' : '<a href="?act=top&amp;mod=karma">' . _t('Karma') . '</a>';
}

switch ($mod) {
    case 'guest':
        // Топ Гостевой
        echo '<div class="phdr"><a href="./"><b>' . _t('Community') . '</b></a> | ' . _t('Most active in Guestbook') . '</div>';
        echo '<div class="topmenu">' . implode(' | ', $menu) . '</div>';
        echo get_top('postguest');
        echo '<div class="phdr"><a href="../guestbook/">' . _t('Guestbook') . '</a></div>';
        break;

    case 'comm':
        // Топ комментариев
        echo '<div class="phdr"><a href="./"><b>' . _t('Community') . '</b></a> | ' . _t('Most commentators') . '</div>';
        echo '<div class="topmenu">' . implode(' | ', $menu) . '</div>';
        echo get_top('komm');
        echo '<div class="phdr"><a href="../">' . _t('Home') . '</a></div>';
        break;

    case 'karma':
        // Топ Кармы
        if ($config->karma) {
            echo '<div class="phdr"><a href="./"><b>' . _t('Community') . '</b></a> | ' . _t('Best Karma') . '</div>';
            echo '<div class="topmenu">' . implode(' | ', $menu) . '</div>';
            $req = $db->query('SELECT *, (`karma_plus` - `karma_minus`) AS `karma` FROM `users` WHERE (`karma_plus` - `karma_minus`) > 0 ORDER BY `karma` DESC LIMIT 9');

            if ($req->rowCount()) {
                while ($res = $req->fetch()) {
                    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                    echo $tools->displayUser($res, ['header' => ('<b>' . $res['karma']) . '</b>']) . '</div>';
                    ++$i;
                }
            } else {
                echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
            }

            echo '<div class="phdr"><a href="../">' . _t('Home') . '</a></div>';
        }
        break;

    default:
        // Топ Форума
        echo '<div class="phdr"><a href="./"><b>' . _t('Community') . '</b></a> | ' . _t('Most active in Forum') . '</div>';
        echo '<div class="topmenu">' . implode(' | ', $menu) . '</div>';
        echo get_top('postforum');
        echo '<div class="phdr"><a href="../forum/">' . _t('Forum') . '</a></div>';
}

echo '<p><a href="./">' . _t('Back') . '</a></p>';

echo $view->render('system::app/old_content', [
    'title'   => _t('Top Activity'),
    'content' => ob_get_clean(),
]);
