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

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

// Каталог пользовательских Аватаров
if ($id && is_dir(ROOT_PATH . 'images/avatars/' . $id)) {
    $avatar = isset($_GET['avatar']) ? intval($_GET['avatar']) : false;

    if ($systemUser->isValid() && $avatar && is_file('../images/avatars/' . $id . '/' . $avatar . '.png')) {
        if (isset($_POST['submit'])) {
            // Устанавливаем пользовательский Аватар
            if (@copy('../images/avatars/' . $id . '/' . $avatar . '.png', '../files/users/avatar/' . $systemUser->id . '.png')) {
                echo '<div class="gmenu"><p>' . _t('Avatar has been successfully applied') . '<br />' .
                    '<a href="../profile/?act=edit">' . _t('Continue') . '</a></p></div>';
            } else {
                echo $tools->displayError(_t('An error occurred'), '<a href="' . $_SESSION['ref'] . '">' . _t('Back') . '</a>');
            }
        } else {
            echo '<div class="phdr"><a href="?act=avatars"><b>' . _t('Avatars') . '</b></a> | ' . _t('Set to Profile') . '</div>' .
                '<div class="rmenu"><p>' . _t('Are you sure you want to set yourself this avatar?') . '</p>' .
                '<p><img src="../images/avatars/' . $id . '/' . $avatar . '.png" alt="" /></p>' .
                '<p><form action="?act=avatars&amp;id=' . $id . '&amp;avatar=' . $avatar . '" method="post"><input type="submit" name="submit" value="' . _t('Save') . '"/></form></p>' .
                '</div>' .
                '<div class="phdr"><a href="?act=avatars&amp;id=' . $id . '">' . _t('Cancel') . '</a></div>';
        }
    } else {
        // Показываем список Аватаров
        echo '<div class="phdr"><a href="?act=avatars"><b>' . _t('Avatars') . '</b></a> | ' . htmlentities(file_get_contents(ROOT_PATH . 'images/avatars/' . $id . '/name.dat'), ENT_QUOTES, 'utf-8') . '</div>';
        $array = glob(ROOT_PATH . 'images/avatars/' . $id . '/*.png');
        $total = count($array);
        $end = $start + $kmess;

        if ($end > $total) {
            $end = $total;
        }

        if ($total > 0) {
            for ($i = $start; $i < $end; $i++) {
                echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                echo '<img src="../images/avatars/' . $id . '/' . basename($array[$i]) . '" alt="" />';

                if ($systemUser->isValid()) {
                    echo ' - <a href="?act=avatars&amp;id=' . $id . '&amp;avatar=' . basename($array[$i]) . '">' . _t('Select') . '</a>';
                }

                echo '</div>';
            }
        } else {
            echo '<div class="menu">' . _t('The list is empty') . '</div>';
        }

        echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

        if ($total > $kmess) {
            echo '<p>' . $tools->displayPagination('?act=avatars&amp;id=' . $id . '&amp;', $start, $total, $kmess) . '</p>' .
                '<p><form action="?act=avatars&amp;id=' . $id . '" method="post">' .
                '<input type="text" name="page" size="2"/>' .
                '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
                '</form></p>';
        }

        echo '<p><a href="?act=avatars">' . _t('Back') . '</a><br />';
    }
} else {
    // Показываем каталоги с Аватарами
    echo '<div class="phdr"><a href="?"><b>' . _t('Information, FAQ') . '</b></a> | ' . _t('Avatars') . '</div>';
    $dir = glob(ROOT_PATH . 'images/avatars/*', GLOB_ONLYDIR);
    $total = 0;
    $total_dir = count($dir);

    for ($i = 0; $i < $total_dir; $i++) {
        $count = (int)count(glob($dir[$i] . '/*.png'));
        $total = $total + $count;
        echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
        echo '<a href="?act=avatars&amp;id=' . basename($dir[$i]) . '">' . htmlentities(file_get_contents($dir[$i] . '/name.dat'), ENT_QUOTES, 'utf-8') .
            '</a> (' . $count . ')</div>';
    }

    echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>' .
        '<p><a href="' . $_SESSION['ref'] . '">' . _t('Back') . '</a></p>';
}
