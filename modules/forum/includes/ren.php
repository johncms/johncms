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

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $user */
$user = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

if ($user->rights == 3 || $user->rights >= 6) {
    if (! $id) {
        echo $tools->displayError(_t('Wrong data'));
        echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
        exit;
    }

    $ms = $db->query("SELECT * FROM `forum_topic` WHERE `id` = '${id}'")->fetch();

    if (empty($ms)) {
        echo $tools->displayError(_t('Wrong data'));
        echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
        exit;
    }

    if (isset($_POST['submit'])) {
        $nn = isset($_POST['nn']) ? trim($_POST['nn']) : '';

        if (! $nn) {
            echo $tools->displayError(_t('You have not entered topic name'), '<a href="?act=ren&amp;id=' . $id . '">' . _t('Repeat') . '</a>');
            echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
            exit;
        }

        // Проверяем, есть ли тема с таким же названием?
        $pt = $db->query("SELECT * FROM `forum_topic` WHERE section_id = '" . $ms['section_id'] . "' AND `name` = " . $db->quote($nn) . ' LIMIT 1');

        if ($pt->rowCount()) {
            echo $tools->displayError(_t('Topic with same name already exists in this section'), '<a href="?act=ren&amp;id=' . $id . '">' . _t('Repeat') . '</a>');
            echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
            exit;
        }

        $db->exec('UPDATE `forum_topic` SET `name` =' . $db->quote($nn) . " WHERE id='" . $id . "'");
        header("Location: ?type=topic&id=${id}");
    } else {
        // Переименовываем тему
        echo '<div class="phdr"><a href="?type=topic&id=' . $id . '"><b>' . _t('Forum') . '</b></a> | ' . _t('Rename Topic') . '</div>' .
            '<div class="menu"><form action="?act=ren&amp;id=' . $id . '" method="post">' .
            '<p><h3>' . _t('Topic name') . '</h3>' .
            '<input type="text" name="nn" value="' . $ms['name'] . '"/></p>' .
            '<p><input type="submit" name="submit" value="' . _t('Save') . '"/></p>' .
            '</form></div>' .
            '<div class="phdr"><a href="?type=topic&id=' . $id . '">' . _t('Back') . '</a></div>';
    }
}
