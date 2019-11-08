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

// Редактирование новости
if ($user->rights >= 6) {
    echo '<div class="phdr"><a href="./"><b>' . _t('News') . '</b></a> | ' . _t('Edit') . '</div>';

    if (! $id) {
        echo $view->render('system::app/old_content', [
            'title'   => $textl,
            'content' => $tools->displayError(_t('Wrong data'), '<a href="./">' . _t('Back to news') . '</a>'),
        ]);
        exit;
    }

    if (isset($_POST['submit'])) {
        $error = [];

        if (empty($_POST['name'])) {
            $error[] = _t('You have not entered news title');
        }

        if (empty($_POST['text'])) {
            $error[] = _t('You have not entered news text');
        }

        $name = htmlspecialchars(trim($_POST['name']));
        $text = trim($_POST['text']);

        if (! $error) {
            $db->prepare('
                      UPDATE `news` SET
                      `name` = ?,
                      `text` = ?
                      WHERE `id` = ?
                    ')->execute([
                $name,
                $text,
                $id,
            ]);
        } else {
            echo $tools->displayError($error, '<a href="?act=edit&amp;id=' . $id . '">' . _t('Repeat') . '</a>');
        }
        echo '<p>' . _t('Article changed') . '<br /><a href="./">' . _t('Continue') . '</a></p>';
    } else {
        $res = $db->query("SELECT * FROM `news` WHERE `id` = '${id}'")->fetch();

        echo '<div class="menu"><form action="?do=edit&amp;id=' . $id . '" method="post">' .
            '<p><h3>' . _t('Title') . '</h3>' .
            '<input type="text" name="name" value="' . $res['name'] . '"/></p>' .
            '<p><h3>' . _t('Text') . '</h3>' .
            '<textarea rows="' . $user->config->fieldHeight . '" name="text">' . htmlentities($res['text'], ENT_QUOTES, 'UTF-8') . '</textarea></p>' .
            '<p><input type="submit" name="submit" value="' . _t('Save') . '"/></p>' .
            '</form></div>' .
            '<div class="phdr"><a href="./">' . _t('Back to news') . '</a></div>';
    }
} else {
    header('location: ./');
}
