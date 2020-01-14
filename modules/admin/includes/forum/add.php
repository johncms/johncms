<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

defined('_IN_JOHNADM') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 * @var Johncms\NavChain $nav_chain
 * @var Johncms\System\Http\Request $request
 */

// Добавление категории
if ($id) {
    // Проверяем наличие категории
    $req = $db->query("SELECT `name` FROM `forum_sections` WHERE `id` = '${id}'");

    if ($req->rowCount()) {
        $res = $req->fetch();
        $cat_name = $res['name'];
    } else {
        echo $tools->displayError(
            __('Wrong data'),
            '<a href="?act=forum">' . __('Forum Management') . '</a>'
        );
        echo $view->render('system::app/old_content', ['content' => ob_get_clean()]);
        exit;
    }
}

if (isset($_POST['submit'])) {
    // Принимаем данные
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $desc = isset($_POST['desc']) ? trim($_POST['desc']) : '';
    $allow = isset($_POST['allow']) ? (int) ($_POST['allow']) : 0;
    $section_type = isset($_POST['section_type']) ? (int) ($_POST['section_type']) : 0;

    // Проверяем на ошибки
    $error = [];

    if (! $name) {
        $error[] = __('You have not entered Title');
    }

    if ($name && (mb_strlen($name) < 2 || mb_strlen($name) > 30)) {
        $error[] = __('Title') . ': ' . __('Invalid length');
    }

    if ($desc && mb_strlen($desc) < 2) {
        $error[] = __('Description should be at least 2 characters in length');
    }

    if (! $error) {
        // Добавляем в базу категорию
        $req = $db->query('SELECT `sort`, parent FROM `forum_sections` WHERE ' . ($id ? "`parent` = '${id}'" : '1=1') . ' ORDER BY `sort` DESC LIMIT 1');

        if ($req->rowCount()) {
            $res = $req->fetch();
            $sort = $res['sort'] + 1;
        } else {
            $sort = 1;
        }

        $db->prepare(
            '
                  INSERT INTO `forum_sections` SET
                  `parent` = ?,
                  `name` = ?,
                  `description` = ?,
                  `access` = ?,
                  `section_type` = ?,
                  `sort` = ?
                '
        )->execute(
            [
                ($id ? $id : 0),
                $name,
                $desc,
                $allow,
                $section_type,
                $sort,
            ]
        );

        header('Location: ?act=forum&mod=cat' . ($id ? '&id=' . $id : ''));
    } else {
        // Выводим сообщение об ошибках
        echo $tools->displayError($error);
    }
} else {
    // Форма ввода
    echo '<div class="phdr"><b>' . ($id ? __('Add Section') : __('Add Category')) . '</b></div>';

    if ($id) {
        echo '<div class="bmenu"><b>' . __('Go to category') . ':</b> ' . $cat_name . '</div>';
    }

    echo '<form action="?act=forum&amp;mod=add' . ($id ? '&amp;id=' . $id : '') . '" method="post">' .
        '<div class="gmenu">' .
        '<p><h3>' . __('Title') . '</h3>' .
        '<input type="text" name="name" />' .
        '<br><small>' . __('Min. 2, Max. 30 characters') . '</small></p>' .
        '<p><h3>' . __('Description') . '</h3>' .
        '<textarea name="desc" rows="' . $user->config->fieldHeight . '"></textarea>' .
        '<br><small>' . __('Optional field') . '<br>' . __('Min. 2, Max. 500 characters') . '</small></p>';

    if ($id) {
        echo '<p><input type="radio" name="allow" value="0" checked="checked"/>&#160;' . __('Common access') . '<br>' .
            '<input type="radio" name="allow" value="4"/>&#160;' . __('Only for reading') . '<br>' .
            '<input type="radio" name="allow" value="2"/>&#160;' . __('Allow authors to edit the 1st post') . '<br>' .
            '<input type="radio" name="allow" value="1"/>&#160;' . __('Assign the newly created authors as curators') . '</p>';
    }

    echo '<h3 style="margin-top: 5px;">' . __('Section type') . '</h3>
                 <p><input type="radio" name="section_type" value="0" checked="checked"/>&#160;' . __('For subsections') . '<br>' .
        '<input type="radio" name="section_type" value="1"/>&#160;' . __('For topics') . '</p>';

    echo '<p><input type="submit" value="' . __('Add') . '" name="submit" />' .
        '</p></div></form>' .
        '<div class="phdr"><a href="?act=forum&amp;mod=cat' . ($id ? '&amp;id=' . $id : '') . '">' . __('Back') . '</a></div>';
}
