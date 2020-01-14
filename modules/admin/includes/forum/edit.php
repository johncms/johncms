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

// Редактирование выбранной категории, или раздела
if (! $id) {
    echo $tools->displayError(__('Wrong data'), '<a href="?act=forum">' . __('Forum Management') . '</a>');
    echo $view->render('system::app/old_content', ['content' => ob_get_clean()]);
    exit;
}

$req = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '${id}'");

if ($req->rowCount()) {
    $res = $req->fetch();

    if (isset($_POST['submit'])) {
        // Принимаем данные
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $desc = isset($_POST['desc']) ? trim($_POST['desc']) : '';
        $sort = isset($_POST['sort']) ? (int) ($_POST['sort']) : 100;
        $section_type = isset($_POST['section_type']) ? (int) ($_POST['section_type']) : 0;
        $category = isset($_POST['category']) ? (int) ($_POST['category']) : 0;
        $allow = isset($_POST['allow']) ? (int) ($_POST['allow']) : 0;

        // проверяем на ошибки
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
            // Записываем в базу
            $db->prepare(
                '
                      UPDATE `forum_sections` SET
                      `name` = ?,
                      `description` = ?,
                      `access` = ?,
                      `sort` = ?,
                      `section_type` = ?
                      WHERE `id` = ?
                    '
            )->execute(
                [
                    $name,
                    $desc,
                    $allow,
                    $sort,
                    $section_type,
                    $id,
                ]
            );

            if ($category != $res['parent']) {
                // Вычисляем сортировку
                $req_s = $db->query("SELECT `sort` FROM `forum_sections` WHERE `parent` = '${category}' ORDER BY `sort` DESC LIMIT 1");
                $res_s = $req_s->fetch();
                $sort = $res_s['sort'] + 1;
                // Меняем категорию
                $db->exec("UPDATE `forum_sections` SET `parent` = '${category}', `sort` = '${sort}' WHERE `id` = '${id}'");
                // Меняем категорию для прикрепленных файлов
                $db->exec("UPDATE `cms_forum_files` SET `cat` = '${category}' WHERE `cat` = '" . $res['parent'] . "'");
            }
            header('Location: ?act=forum&mod=cat' . (! empty($res['parent']) ? '&id=' . $res['parent'] : ''));
        } else {
            // Выводим сообщение об ошибках
            echo $tools->displayError($error);
        }
    } else {
        // Форма ввода
        echo '<div class="phdr"><b>' . __('Edit Section') . '</b></div>' .
            '<form action="?act=forum&amp;mod=edit&amp;id=' . $id . '" method="post">' .
            '<div class="gmenu">' .
            '<p><h3>' . __('Title') . '</h3>' .
            '<input type="text" name="name" value="' . $res['name'] . '"/>' .
            '<p><h3>' . __('Order') . '</h3>' .
            '<input type="text" name="sort" value="' . $res['sort'] . '"/><br>' .
            '<br><small>' . __('Min. 2, Max. 30 characters') . '</small></p>' .
            '<p><h3>' . __('Description') . '</h3>' .
            '<textarea name="desc" rows="' . $user->config->fieldHeight . '">' . str_replace(
                '<br>',
                "\r\n",
                $res['description']
            ) . '</textarea>' .
            '<br><small>' . __('Optional field') . '<br>' . __('Min. 2, Max. 500 characters') . '</small></p>';

        $allow = ! empty($res['access']) ? (int) ($res['access']) : 0;
        echo '<p><input type="radio" name="allow" value="0" ' . (! $allow ? 'checked="checked"' : '') . '/>&#160;' . __('Common access') . '<br>' .
            '<input type="radio" name="allow" value="4" ' . ($allow == 4 ? 'checked="checked"' : '') . '/>&#160;' . __('Only for reading') . '<br>' .
            '<input type="radio" name="allow" value="2" ' . ($allow == 2 ? 'checked="checked"' : '') . '/>&#160;' . __('Allow authors to edit the 1st post') . '<br>' .
            '<input type="radio" name="allow" value="1" ' . ($allow == 1 ? 'checked="checked"' : '') . '/>&#160;' . __('Assign the newly created authors as curators') . '</p>';
        echo '<p><h3>' . __('Category') . '</h3><select name="category" size="1">';

        echo '<option value="0" ' . (empty($res['parent']) ? ' selected="selected"' : '') . '>-</option>';
        $req_c = $db->query("SELECT * FROM `forum_sections` WHERE `id` != '" . $res['is'] . "' ORDER BY `sort` ASC");

        while ($res_c = $req_c->fetch()) {
            echo '<option value="' . $res_c['id'] . '"' . ($res_c['id'] == $res['parent'] ? ' selected="selected"' : '') . '>' . $res_c['name'] . '</option>';
        }
        echo '</select></p>';

        $section_type = ! empty($res['section_type']) ? (int) ($res['section_type']) : 0;
        echo '<h3 style="margin-top: 5px;">' . __('Section type') . '</h3>
                    <p><input type="radio" name="section_type" value="0" ' . (! $section_type ? 'checked="checked"' : '') . '/>&#160;' . __('For subsections') . '<br>' .
            '<input type="radio" name="section_type" value="1" ' . ($section_type == 1 ? 'checked="checked"' : '') . '/>&#160;' . __('For topics') . '</p>';

        echo '<p><input type="submit" value="' . __('Save') . '" name="submit" />' .
            '</p></div></form>' .
            '<div class="phdr"><a href="?act=forum&amp;mod=cat' . (! empty($res['parent']) ? '&amp;id=' . $res['parent'] : '') . '">' . __('Back') . '</a></div>';
    }
} else {
    header('Location: ?act=forum&mod=cat');
}
