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

// Удаление категории, или раздела
if (! $id) {
    echo $tools->displayError(__('Wrong data'), '<a href="?act=forum">' . __('Forum Management') . '</a>');
    echo $view->render('system::app/old_content', ['content' => ob_get_clean()]);
    exit;
}

$req = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '${id}'");

if ($req->rowCount()) {
    $res = $req->fetch();
    echo '<div class="phdr"><b>' . ($res['section_type'] != 1 ? __('Delete section') : __('Delete category')) . ':</b> ' . $res['name'] . '</div>';

    // Проверяем, есть ли подчиненная информация
    if (! empty($res['section_type'])) {
        $total = $db->query("SELECT COUNT(*) FROM `forum_topic` WHERE `section_id` = '${id}'")->fetchColumn();
    } else {
        $total = $db->query("SELECT COUNT(*) FROM `forum_sections` WHERE `parent` = '${id}'")->fetchColumn();
    }

    if ($total) {
        if (empty($res['section_type'])) {
            // Удаление категории с подчиненными данными
            if (isset($_POST['submit'])) {
                $category = isset($_POST['category']) ? (int) ($_POST['category']) : 0;

                if (! $category || $category == $id) {
                    echo $tools->displayError(__('Wrong data'));
                    echo $view->render('system::app/old_content', ['content' => ob_get_clean()]);
                    exit;
                }

                $check = $db->query("SELECT COUNT(*) FROM `forum_sections` WHERE `id` = '${category}'")->fetchColumn();

                if (! $check) {
                    echo $tools->displayError(__('Wrong data'));
                    echo $view->render('system::app/old_content', ['content' => ob_get_clean()]);
                    exit;
                }

                // Вычисляем правила сортировки и перемещаем разделы
                $sort = $db->query("SELECT * FROM `forum_sections` WHERE `parent` = '${category}' ORDER BY `sort` DESC")->fetch();
                $sortnum = ! empty($sort['sort']) && $sort['sort'] > 0 ? $sort['sort'] + 1 : 1;
                $req_c = $db->query("SELECT * FROM `forum_sections` WHERE `parent` = '${id}'");

                while ($res_c = $req_c->fetch()) {
                    $db->exec("UPDATE `forum_sections` SET `parent` = '" . $category . "', `sort` = '${sortnum}' WHERE `id` = " . $res_c['id']);
                    ++$sortnum;
                }

                // Перемещаем файлы в выбранную категорию
                $db->exec("UPDATE `cms_forum_files` SET `cat` = '" . $category . "' WHERE `cat` = " . $res['id']);
                $db->exec("DELETE FROM `forum_sections` WHERE `id` = '${id}'");
                echo '<div class="rmenu"><p><h3>' . __('Category deleted') . '</h3>' . __('All content has been moved to') . ' <a href="../forum/?id=' . $category . '">' . __('selected category') . '</a></p></div>';
            } else {
                echo '<form action="?act=forum&amp;mod=del&amp;id=' . $id . '" method="POST">' .
                    '<div class="rmenu"><p>' . __('<h3>WARNING!</h3>There are subsections. Move them to another category.') . '</p>' .
                    '<p><h3>' . __('Select category') . '</h3><select name="category" size="1">';
                $req_c = $db->query("SELECT * FROM `forum_sections` WHERE (`section_type` != 1 OR section_type IS NULL) AND `id` != '${id}' ORDER BY `sort` ASC");

                while ($res_c = $req_c->fetch()) {
                    echo '<option value="' . $res_c['id'] . '">' . $res_c['name'] . '</option>';
                }

                echo '</select><br><small>' . __('All categories, topics, and files will be moved into selected category. Old category will be removed.') . '</small></p>' .
                    '<p><input type="submit" name="submit" value="' . __('Move') . '" /></p></div>';

                // Для супервайзоров запрос на полное удаление
                if ($user->rights == 9) {
                    echo '<div class="rmenu"><p><h3>' . __('Complete removal') . '</h3>' . __('If you want to destroy all the information, first remove') .
                        ' <a href="?act=forum&amp;mod=cat&amp;id=' . $id . '">' . __('subsections') . '</a></p></div>';
                }

                echo '</form>';
            }
        } elseif (isset($_POST['submit'])) {
            // Удаление раздела с подчиненными данными
            // Предварительные проверки
            $subcat = isset($_POST['subcat']) ? (int) ($_POST['subcat']) : 0;

            if (! $subcat || $subcat == $id) {
                echo $tools->displayError(
                    __('Wrong data'),
                    '<a href="?act=forum">' . __('Forum Management') . '</a>'
                );
                echo $view->render('system::app/old_content', ['content' => ob_get_clean()]);
                exit;
            }

            $check = $db->query("SELECT COUNT(*) FROM `forum_sections` WHERE `id` = '${subcat}' AND `section_type` = 1")->fetchColumn();

            if (! $check) {
                echo $tools->displayError(
                    __('Wrong data'),
                    '<a href="?act=forum">' . __('Forum Management') . '</a>'
                );
                echo $view->render('system::app/old_content', ['content' => ob_get_clean()]);
                exit;
            }

            $db->exec("UPDATE `forum_topic` SET `section_id` = '${subcat}' WHERE `section_id` = '${id}'");
            $db->exec("UPDATE `cms_forum_files` SET `subcat` = '${subcat}' WHERE `subcat` = '${id}'");
            $db->exec("DELETE FROM `forum_sections` WHERE `id` = '${id}'");
            echo '<div class="rmenu"><p><h3>' . __('Section deleted') . '</h3>' . __('All content has been moved to') . ' <a href="../forum/?id=' . $subcat . '">' . __('selected section') . '</a>.' .
                '</p></div>';
        } elseif (isset($_POST['delete'])) {
            if ($user->rights != 9) {
                echo $tools->displayError(__('Access forbidden'));
                echo $view->render('system::app/old_content', ['content' => ob_get_clean()]);
                exit;
            }

            // Удаляем файлы
            $req_f = $db->query("SELECT * FROM `cms_forum_files` WHERE `subcat` = '${id}'");

            while ($res_f = $req_f->fetch()) {
                unlink('../files/forum/attach/' . $res_f['filename']);
            }

            $db->exec("DELETE FROM `cms_forum_files` WHERE `subcat` = '${id}'");

            // Удаляем посты, голосования и метки прочтений
            $req_t = $db->query("SELECT `id` FROM `forum_topic` WHERE `section_id` = '${id}'");

            while ($res_t = $req_t->fetch()) {
                $db->exec("DELETE FROM `forum_messages` WHERE `topic_id` = '" . $res_t['id'] . "'");
                $db->exec("DELETE FROM `cms_forum_vote` WHERE `topic` = '" . $res_t['id'] . "'");
                $db->exec("DELETE FROM `cms_forum_vote_users` WHERE `topic` = '" . $res_t['id'] . "'");
                $db->exec("DELETE FROM `cms_forum_rdm` WHERE `topic_id` = '" . $res_t['id'] . "'");
            }

            // Удаляем темы
            $db->exec("DELETE FROM `forum_topic` WHERE `section_id` = '${id}'");
            // Удаляем раздел
            $db->exec("DELETE FROM `forum_sections` WHERE `id` = '${id}'");
            // Оптимизируем таблицы
            $db->query('OPTIMIZE TABLE `cms_forum_files` , `cms_forum_rdm` , `cms_forum_vote` , `cms_forum_vote_users`');
            echo '<div class="rmenu"><p>' . __('Section with all contents are removed') . '<br>' .
                '<a href="?act=forum&amp;mod=cat&amp;id=' . $res['parent'] . '">' . __('Go to category') . '</a></p></div>';
        } else {
            echo '<form action="?act=forum&amp;mod=del&amp;id=' . $id . '" method="POST"><div class="rmenu">' .
                '<p>' . __('<h3>WARNING!</h3>There are topics in the section. You must move them to another section.') . '</p>' . '<p><h3>' . __('Select section') . '</h3>';
            $cat = isset($_GET['cat']) ? abs((int) ($_GET['cat'])) : 0;
            $ref = $cat ? $cat : $res['parent'];
            $req_r = $db->query("SELECT * FROM `forum_sections` WHERE `parent` = '${ref}' AND `id` != '${id}' ORDER BY `sort` ASC");

            while ($res_r = $req_r->fetch()) {
                echo '<input type="radio" name="subcat" value="' . $res_r['id'] . '" />&#160;' . $res_r['name'] . '<br>';
            }

            echo '</p><p><h3>' . __('Other category') . '</h3><ul>';
            $req_c = $db->query("SELECT * FROM `forum_sections` WHERE `id` != '${ref}' AND parent = 0 ORDER BY `sort` ASC");

            while ($res_c = $req_c->fetch()) {
                echo '<li><a href="?act=forum&amp;mod=del&amp;id=' . $id . '&amp;cat=' . $res_c['id'] . '">' . $res_c['name'] . '</a></li>';
            }

            echo '</ul><small>' . __('All the topics and files will be moved to selected section. Old section will be deleted.') . '</small></p><p><input type="submit" name="submit" value="' . __('Move') . '" /></p></div>';

            if ($user->rights == 9) {
                // Для супервайзоров запрос на полное удаление
                echo '<div class="rmenu"><p><h3>' . __('Complete removal') . '</h3>' . __('WARNING! All the information will be deleted');
                echo '</p><p><input type="submit" name="delete" value="' . __('Delete') . '" /></p></div>';
            }

            echo '</form>';
        }
    } elseif (isset($_POST['submit'])) {
        // Удаление пустого раздела, или категории
        $db->exec("DELETE FROM `forum_section` WHERE `id` = '${id}'");
        echo '<div class="rmenu"><p>' . ($res['type'] == 'r' ? __('Section deleted') : __('Category deleted')) . '</p></div>';
    } else {
        echo '<div class="rmenu"><p>' . __('Do you really want to delete?') . '</p>' .
            '<p><form action="?act=forum&amp;mod=del&amp;id=' . $id . '" method="POST">' .
            '<input type="submit" name="submit" value="' . __('Delete') . '" />' .
            '</form></p></div>';
    }
    echo '<div class="phdr"><a href="?act=forum&amp;mod=cat">' . __('Back') . '</a></div>';
} else {
    header('Location: ?act=forum&mod=cat');
}
