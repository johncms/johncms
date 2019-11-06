<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNADM') || die('Error: restricted access');
ob_start(); // Перехват вывода скриптов без шаблона

/**
 * @var PDO                        $db
 * @var Johncms\Api\ToolsInterface $tools
 * @var Johncms\Api\UserInterface  $user
 */

$set_forum = unserialize($user->set_forum);

if (! isset($set_forum) || empty($set_forum)) {
    $set_forum = [
        'farea'    => 0,
        'upfp'     => 0,
        'farea_w'  => 20,
        'farea_h'  => 4,
        'postclip' => 1,
        'postcut'  => 2,
    ];
}

switch ($mod) {
    case 'del':

        // Удаление категории, или раздела
        if (! $id) {
            echo $tools->displayError(_t('Wrong data'), '<a href="?act=forum">' . _t('Forum Management') . '</a>');
            echo $view->render('system::app/old_content', ['content' => ob_get_clean()]);
            exit;
        }

        $req = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '${id}'");

        if ($req->rowCount()) {
            $res = $req->fetch();
            echo '<div class="phdr"><b>' . ($res['section_type'] != 1 ? _t('Delete section') : _t('Delete category')) . ':</b> ' . $res['name'] . '</div>';

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
                            echo $tools->displayError(_t('Wrong data'));
                            echo $view->render('system::app/old_content', ['content' => ob_get_clean()]);
                            exit;
                        }

                        $check = $db->query("SELECT COUNT(*) FROM `forum_sections` WHERE `id` = '${category}'")->fetchColumn();

                        if (! $check) {
                            echo $tools->displayError(_t('Wrong data'));
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
                        echo '<div class="rmenu"><p><h3>' . _t('Category deleted') . '</h3>' . _t('All content has been moved to') . ' <a href="../forum/?id=' . $category . '">' . _t('selected category') . '</a></p></div>';
                    } else {
                        echo '<form action="?act=forum&amp;mod=del&amp;id=' . $id . '" method="POST">' .
                            '<div class="rmenu"><p>' . _t('<h3>WARNING!</h3>There are subsections. Move them to another category.') . '</p>' .
                            '<p><h3>' . _t('Select category') . '</h3><select name="category" size="1">';
                        $req_c = $db->query("SELECT * FROM `forum_sections` WHERE (`section_type` != 1 OR section_type IS NULL) AND `id` != '${id}' ORDER BY `sort` ASC");

                        while ($res_c = $req_c->fetch()) {
                            echo '<option value="' . $res_c['id'] . '">' . $res_c['name'] . '</option>';
                        }

                        echo '</select><br><small>' . _t('All categories, topics, and files will be moved into selected category. Old category will be removed.') . '</small></p>' .
                            '<p><input type="submit" name="submit" value="' . _t('Move') . '" /></p></div>';

                        // Для супервайзоров запрос на полное удаление
                        if ($user->rights == 9) {
                            echo '<div class="rmenu"><p><h3>' . _t('Complete removal') . '</h3>' . _t('If you want to destroy all the information, first remove') . ' <a href="?act=forum&amp;mod=cat&amp;id=' . $id . '">' . _t('subsections') . '</a></p></div>';
                        }

                        echo '</form>';
                    }
                } else {
                    // Удаление раздела с подчиненными данными
                    if (isset($_POST['submit'])) {
                        // Предварительные проверки
                        $subcat = isset($_POST['subcat']) ? (int) ($_POST['subcat']) : 0;

                        if (! $subcat || $subcat == $id) {
                            echo $tools->displayError(_t('Wrong data'),
                                '<a href="?act=forum">' . _t('Forum Management') . '</a>');
                            echo $view->render('system::app/old_content', ['content' => ob_get_clean()]);
                            exit;
                        }

                        $check = $db->query("SELECT COUNT(*) FROM `forum_sections` WHERE `id` = '${subcat}' AND `section_type` = 1")->fetchColumn();

                        if (! $check) {
                            echo $tools->displayError(_t('Wrong data'),
                                '<a href="?act=forum">' . _t('Forum Management') . '</a>');
                            echo $view->render('system::app/old_content', ['content' => ob_get_clean()]);
                            exit;
                        }

                        $db->exec("UPDATE `forum_topic` SET `section_id` = '${subcat}' WHERE `section_id` = '${id}'");
                        $db->exec("UPDATE `cms_forum_files` SET `subcat` = '${subcat}' WHERE `subcat` = '${id}'");
                        $db->exec("DELETE FROM `forum_sections` WHERE `id` = '${id}'");
                        echo '<div class="rmenu"><p><h3>' . _t('Section deleted') . '</h3>' . _t('All content has been moved to') . ' <a href="../forum/?id=' . $subcat . '">' . _t('selected section') . '</a>.' .
                            '</p></div>';
                    } elseif (isset($_POST['delete'])) {
                        if ($user->rights != 9) {
                            echo $tools->displayError(_t('Access forbidden'));
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
                        echo '<div class="rmenu"><p>' . _t('Section with all contents are removed') . '<br>' .
                            '<a href="?act=forum&amp;mod=cat&amp;id=' . $res['parent'] . '">' . _t('Go to category') . '</a></p></div>';
                    } else {
                        echo '<form action="?act=forum&amp;mod=del&amp;id=' . $id . '" method="POST"><div class="rmenu">' .
                            '<p>' . _t('<h3>WARNING!</h3>There are topics in the section. You must move them to another section.') . '</p>' . '<p><h3>' . _t('Select section') . '</h3>';
                        $cat = isset($_GET['cat']) ? abs((int) ($_GET['cat'])) : 0;
                        $ref = $cat ? $cat : $res['parent'];
                        $req_r = $db->query("SELECT * FROM `forum_sections` WHERE `parent` = '${ref}' AND `id` != '${id}' ORDER BY `sort` ASC");

                        while ($res_r = $req_r->fetch()) {
                            echo '<input type="radio" name="subcat" value="' . $res_r['id'] . '" />&#160;' . $res_r['name'] . '<br>';
                        }

                        echo '</p><p><h3>' . _t('Other category') . '</h3><ul>';
                        $req_c = $db->query("SELECT * FROM `forum_sections` WHERE `id` != '${ref}' AND parent = 0 ORDER BY `sort` ASC");

                        while ($res_c = $req_c->fetch()) {
                            echo '<li><a href="?act=forum&amp;mod=del&amp;id=' . $id . '&amp;cat=' . $res_c['id'] . '">' . $res_c['name'] . '</a></li>';
                        }

                        echo '</ul><small>' . _t('All the topics and files will be moved to selected section. Old section will be deleted.') . '</small></p><p><input type="submit" name="submit" value="' . _t('Move') . '" /></p></div>';

                        if ($user->rights == 9) {
                            // Для супервайзоров запрос на полное удаление
                            echo '<div class="rmenu"><p><h3>' . _t('Complete removal') . '</h3>' . _t('WARNING! All the information will be deleted');
                            echo '</p><p><input type="submit" name="delete" value="' . _t('Delete') . '" /></p></div>';
                        }

                        echo '</form>';
                    }
                }
            } else {
                // Удаление пустого раздела, или категории
                if (isset($_POST['submit'])) {
                    $db->exec("DELETE FROM `forum_section` WHERE `id` = '${id}'");
                    echo '<div class="rmenu"><p>' . ($res['type'] == 'r' ? _t('Section deleted') : _t('Category deleted')) . '</p></div>';
                } else {
                    echo '<div class="rmenu"><p>' . _t('Do you really want to delete?') . '</p>' .
                        '<p><form action="?act=forum&amp;mod=del&amp;id=' . $id . '" method="POST">' .
                        '<input type="submit" name="submit" value="' . _t('Delete') . '" />' .
                        '</form></p></div>';
                }
            }
            echo '<div class="phdr"><a href="?act=forum&amp;mod=cat">' . _t('Back') . '</a></div>';
        } else {
            header('Location: ?act=forum&mod=cat');
        }

        break;

    case 'add':
        // Добавление категории
        if ($id) {
            // Проверяем наличие категории
            $req = $db->query("SELECT `name` FROM `forum_sections` WHERE `id` = '${id}'");

            if ($req->rowCount()) {
                $res = $req->fetch();
                $cat_name = $res['text'];
            } else {
                echo $tools->displayError(_t('Wrong data'),
                    '<a href="?act=forum">' . _t('Forum Management') . '</a>');
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
                $error[] = _t('You have not entered Title');
            }

            if ($name && (mb_strlen($name) < 2 || mb_strlen($name) > 30)) {
                $error[] = _t('Title') . ': ' . _t('Invalid length');
            }

            if ($desc && mb_strlen($desc) < 2) {
                $error[] = _t('Description should be at least 2 characters in length');
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

                $db->prepare('
                  INSERT INTO `forum_sections` SET
                  `parent` = ?,
                  `name` = ?,
                  `description` = ?,
                  `access` = ?,
                  `section_type` = ?,
                  `sort` = ?
                ')->execute([
                    ($id ? $id : 0),
                    $name,
                    $desc,
                    $allow,
                    $section_type,
                    $sort,
                ]);

                header('Location: ?act=forum&mod=cat' . ($id ? '&id=' . $id : ''));
            } else {
                // Выводим сообщение об ошибках
                echo $tools->displayError($error);
            }
        } else {
            // Форма ввода
            echo '<div class="phdr"><b>' . ($id ? _t('Add Section') : _t('Add Category')) . '</b></div>';

            if ($id) {
                echo '<div class="bmenu"><b>' . _t('Go to category') . ':</b> ' . $cat_name . '</div>';
            }

            echo '<form action="?act=forum&amp;mod=add' . ($id ? '&amp;id=' . $id : '') . '" method="post">' .
                '<div class="gmenu">' .
                '<p><h3>' . _t('Title') . '</h3>' .
                '<input type="text" name="name" />' .
                '<br><small>' . _t('Min. 2, Max. 30 characters') . '</small></p>' .
                '<p><h3>' . _t('Description') . '</h3>' .
                '<textarea name="desc" rows="' . $user->config->fieldHeight . '"></textarea>' .
                '<br><small>' . _t('Optional field') . '<br>' . _t('Min. 2, Max. 500 characters') . '</small></p>';

            if ($id) {
                echo '<p><input type="radio" name="allow" value="0" checked="checked"/>&#160;' . _t('Common access') . '<br>' .
                    '<input type="radio" name="allow" value="4"/>&#160;' . _t('Only for reading') . '<br>' .
                    '<input type="radio" name="allow" value="2"/>&#160;' . _t('Allow authors to edit the 1st post') . '<br>' .
                    '<input type="radio" name="allow" value="1"/>&#160;' . _t('Assign the newly created authors as curators') . '</p>';
            }

            echo '<h3 style="margin-top: 5px;">' . _t('Section type') . '</h3>
                 <p><input type="radio" name="section_type" value="0" checked="checked"/>&#160;' . _t('For subsections') . '<br>' .
                '<input type="radio" name="section_type" value="1"/>&#160;' . _t('For topics') . '</p>';

            echo '<p><input type="submit" value="' . _t('Add') . '" name="submit" />' .
                '</p></div></form>' .
                '<div class="phdr"><a href="?act=forum&amp;mod=cat' . ($id ? '&amp;id=' . $id : '') . '">' . _t('Back') . '</a></div>';
        }
        break;

    case 'edit':
        // Редактирование выбранной категории, или раздела
        if (! $id) {
            echo $tools->displayError(_t('Wrong data'), '<a href="?act=forum">' . _t('Forum Management') . '</a>');
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
                    $error[] = _t('You have not entered Title');
                }

                if ($name && (mb_strlen($name) < 2 || mb_strlen($name) > 30)) {
                    $error[] = _t('Title') . ': ' . _t('Invalid length');
                }

                if ($desc && mb_strlen($desc) < 2) {
                    $error[] = _t('Description should be at least 2 characters in length');
                }

                if (! $error) {
                    // Записываем в базу
                    $db->prepare('
                      UPDATE `forum_sections` SET
                      `name` = ?,
                      `description` = ?,
                      `access` = ?,
                      `sort` = ?,
                      `section_type` = ?
                      WHERE `id` = ?
                    ')->execute([
                        $name,
                        $desc,
                        $allow,
                        $sort,
                        $section_type,
                        $id,
                    ]);

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
                echo '<div class="phdr"><b>' . _t('Edit Section') . '</b></div>' .
                    '<form action="?act=forum&amp;mod=edit&amp;id=' . $id . '" method="post">' .
                    '<div class="gmenu">' .
                    '<p><h3>' . _t('Title') . '</h3>' .
                    '<input type="text" name="name" value="' . $res['name'] . '"/>' .
                    '<p><h3>' . _t('Order') . '</h3>' .
                    '<input type="text" name="sort" value="' . $res['sort'] . '"/><br>' .
                    '<br><small>' . _t('Min. 2, Max. 30 characters') . '</small></p>' .
                    '<p><h3>' . _t('Description') . '</h3>' .
                    '<textarea name="desc" rows="' . $user->config->fieldHeight . '">' . str_replace('<br>',
                        "\r\n", $res['description']) . '</textarea>' .
                    '<br><small>' . _t('Optional field') . '<br>' . _t('Min. 2, Max. 500 characters') . '</small></p>';

                $allow = ! empty($res['access']) ? (int) ($res['access']) : 0;
                echo '<p><input type="radio" name="allow" value="0" ' . (! $allow ? 'checked="checked"' : '') . '/>&#160;' . _t('Common access') . '<br>' .
                    '<input type="radio" name="allow" value="4" ' . ($allow == 4 ? 'checked="checked"' : '') . '/>&#160;' . _t('Only for reading') . '<br>' .
                    '<input type="radio" name="allow" value="2" ' . ($allow == 2 ? 'checked="checked"' : '') . '/>&#160;' . _t('Allow authors to edit the 1st post') . '<br>' .
                    '<input type="radio" name="allow" value="1" ' . ($allow == 1 ? 'checked="checked"' : '') . '/>&#160;' . _t('Assign the newly created authors as curators') . '</p>';
                echo '<p><h3>' . _t('Category') . '</h3><select name="category" size="1">';

                echo '<option value="0" ' . (empty($res['parent']) ? ' selected="selected"' : '') . '>-</option>';
                $req_c = $db->query("SELECT * FROM `forum_sections` WHERE `id` != '" . $res['is'] . "' ORDER BY `sort` ASC");

                while ($res_c = $req_c->fetch()) {
                    echo '<option value="' . $res_c['id'] . '"' . ($res_c['id'] == $res['parent'] ? ' selected="selected"' : '') . '>' . $res_c['name'] . '</option>';
                }
                echo '</select></p>';

                $section_type = ! empty($res['section_type']) ? (int) ($res['section_type']) : 0;
                echo '<h3 style="margin-top: 5px;">' . _t('Section type') . '</h3>
                    <p><input type="radio" name="section_type" value="0" ' . (! $section_type ? 'checked="checked"' : '') . '/>&#160;' . _t('For subsections') . '<br>' .
                    '<input type="radio" name="section_type" value="1" ' . ($section_type == 1 ? 'checked="checked"' : '') . '/>&#160;' . _t('For topics') . '</p>';

                echo '<p><input type="submit" value="' . _t('Save') . '" name="submit" />' .
                    '</p></div></form>' .
                    '<div class="phdr"><a href="?act=forum&amp;mod=cat' . (! empty($res['parent']) ? '&amp;id=' . $res['parent'] : '') . '">' . _t('Back') . '</a></div>';
            }
        } else {
            header('Location: ?act=forum&mod=cat');
        }
        break;

    case 'cat':
        // Управление категориями и разделами
        echo '<div class="phdr"><a href="?act=forum"><b>' . _t('Forum Management') . '</b></a> | ' . _t('Forum structure') . '</div>';

        if ($id) {
            // Управление разделами
            $req = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '${id}'");
            $res = $req->fetch();
            echo '<div class="bmenu"><a href="?act=forum&amp;mod=cat' . (! empty($res['parent']) ? '&amp;id=' . $res['parent'] : '') . '"><b>' . $res['name'] . '</b></a> | ' . _t('List of sections') . '</div>';
            $req = $db->query("SELECT * FROM `forum_sections` WHERE `parent` = '${id}' ORDER BY `sort` ASC");

            if ($req->rowCount()) {
                $i = 0;

                while ($res = $req->fetch()) {
                    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                    echo '[' . $res['sort'] . '] <a href="?act=forum&amp;mod=cat&amp;id=' . $res['id'] . '"><b>' . $res['name'] . '</b></a>' .
                        '&#160;<a href="../forum/?id=' . $res['id'] . '">&gt;&gt;</a>';

                    if (! empty($res['description'])) {
                        echo '<br><span class="gray"><small>' . $res['description'] . '</small></span><br>';
                    }

                    echo '<div class="sub">' .
                        '<a href="?act=forum&amp;mod=edit&amp;id=' . $res['id'] . '">' . _t('Edit') . '</a> | ' .
                        '<a href="?act=forum&amp;mod=del&amp;id=' . $res['id'] . '">' . _t('Delete') . '</a>' .
                        '</div></div>';
                    ++$i;
                }
            } else {
                echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
            }
        } else {
            // Управление категориями
            echo '<div class="bmenu">' . _t('List of categories') . '</div>';
            $req = $db->query('SELECT * FROM `forum_sections` WHERE `parent` = 0 OR `parent` IS NULL ORDER BY `sort` ASC');
            $i = 0;

            while ($res = $req->fetch()) {
                echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                echo '[' . $res['sort'] . '] <a href="?act=forum&amp;mod=cat&amp;id=' . $res['id'] . '"><b>' . $res['name'] . '</b></a> ' .
                    '(' . $db->query("SELECT COUNT(*) FROM `forum_sections` WHERE `parent` = '" . $res['id'] . "'")->fetchColumn() . ')' .
                    '&#160;<a href="../forum/?id=' . $res['id'] . '">&gt;&gt;</a>';

                if (! empty($res['description'])) {
                    echo '<br><span class="gray"><small>' . $res['description'] . '</small></span><br>';
                }

                echo '<div class="sub">' .
                    '<a href="?act=forum&amp;mod=edit&amp;id=' . $res['id'] . '">' . _t('Edit') . '</a> | ' .
                    '<a href="?act=forum&amp;mod=del&amp;id=' . $res['id'] . '">' . _t('Delete') . '</a>' .
                    '</div></div>';
                ++$i;
            }
        }

        echo '<div class="gmenu">' .
            '<form action="?act=forum&amp;mod=add' . ($id ? '&amp;id=' . $id : '') . '" method="post">' .
            '<input type="submit" value="' . _t('Add') . '" />' .
            '</form></div>' .
            '<div class="phdr">' . ($mod == 'cat' && $id ? '<a href="?act=forum&amp;mod=cat">' . _t('List of categories') . '</a>' : '<a href="?act=forum">' . _t('Forum Management') . '</a>') . '</div>';
        break;

    case 'htopics':
        // Управление скрытыми темами форума
        echo '<div class="phdr"><a href="?act=forum"><b>' . _t('Forum Management') . '</b></a> | ' . _t('Hidden topics') . '</div>';
        $sort = '';
        $link = '';

        if (isset($_GET['usort'])) {
            $sort = " AND `forum_topic`.`user_id` = '" . abs((int) ($_GET['usort'])) . "'";
            $link = '&amp;usort=' . abs((int) ($_GET['usort']));
            echo '<div class="bmenu">' . _t('Filter by author') . ' <a href="?act=forum&amp;mod=htopics">[x]</a></div>';
        }

        if (isset($_GET['rsort'])) {
            $sort = " AND `forum_topic`.`section_id` = '" . abs((int) ($_GET['rsort'])) . "'";
            $link = '&amp;rsort=' . abs((int) ($_GET['rsort']));
            echo '<div class="bmenu">' . _t('Filter by section') . ' <a href="?act=forum&amp;mod=htopics">[x]</a></div>';
        }

        if (isset($_POST['deltopic'])) {
            if ($user->rights != 9) {
                echo $tools->displayError(_t('Access forbidden'));
                echo $view->render('system::app/old_content', ['content' => ob_get_clean()]);
                exit;
            }

            $req = $db->query("SELECT `id` FROM `forum_topic` WHERE `deleted` = '1' ${sort}");

            while ($res = $req->fetch()) {
                $req_f = $db->query('SELECT * FROM `cms_forum_files` WHERE `topic` = ' . $res['id']);

                if ($req_f->rowCount()) {
                    // Удаляем файлы
                    while ($res_f = $req_f->fetch()) {
                        unlink('../files/forum/attach/' . $res_f['filename']);
                    }
                    $db->exec('DELETE FROM `cms_forum_files` WHERE `topic` = ' . $res['id']);
                }
                // Удаляем посты
                $db->exec('DELETE FROM `forum_messages` WHERE `topic_id` = ' . $res['id']);
            }
            // Удаляем темы
            $db->exec("DELETE FROM `forum_topic` WHERE `deleted` = '1' ${sort}");

            header('Location: ?act=forum&mod=htopics');
        } else {
            $total = $db->query("SELECT COUNT(*) FROM `forum_topic` WHERE `deleted` = '1' ${sort}")->fetchColumn();

            if ($total > $user->config->kmess) {
                echo '<div class="topmenu">' . $tools->displayPagination('?act=forum&amp;mod=htopics&amp;', $start, $total, $user->config->kmess) . '</div>';
            }

            $req = $db->query("SELECT `forum_topic`.*, 
            `forum_topic`.`id` AS `fid`, 
            `forum_topic`.`name` AS `topic_name`, 
            `forum_topic`.`user_id` AS `id`, 
            `forum_topic`.`user_name` AS `name`, 
            `users`.`rights`, 
            `users`.`lastdate`, 
            `users`.`sex`, 
            `users`.`status`, 
            `users`.`datereg`,
            `users`.`ip`,
            `users`.`browser`,
            `users`.`ip_via_proxy`
            FROM `forum_topic` LEFT JOIN `users` ON `forum_topic`.`user_id` = `users`.`id`
            WHERE `forum_topic`.`deleted` = '1' ${sort} ORDER BY `forum_topic`.`id` DESC LIMIT " . $start . ',' . $user->config->kmess);

            if ($req->rowCount()) {
                $i = 0;

                while ($res = $req->fetch()) {
                    $subcat = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '" . $res['section_id'] . "'")->fetch();
                    $cat = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '" . $subcat['parent'] . "'")->fetch();
                    $ttime = '<span class="gray">(' . $tools->displayDate($res['mod_last_post_date']) . ')</span>';
                    $text = '<a href="../forum/?type=topic&id=' . $res['fid'] . '"><b>' . $res['topic_name'] . '</b></a>';
                    $text .= '<br><small><a href="../forum/?id=' . $cat['id'] . '">' . $cat['name'] . '</a> / <a href="../forum/?type=topics&id=' . $subcat['id'] . '">' . $subcat['name'] . '</a></small>';
                    $subtext = '<span class="gray">' . _t('Filter') . ':</span> ';
                    $subtext .= '<a href="?act=forum&amp;mod=htopics&amp;rsort=' . $res['section_id'] . '">' . _t('by section') . '</a> | ';
                    $subtext .= '<a href="?act=forum&amp;mod=htopics&amp;usort=' . $res['user_id'] . '">' . _t('by author') . '</a>';
                    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                    echo $tools->displayUser($res, [
                        'header' => $ttime,
                        'body'   => $text,
                        'sub'    => $subtext,
                    ]);
                    echo '</div>';
                    ++$i;
                }

                if ($user->rights == 9) {
                    echo '<form action="?act=forum&amp;mod=htopics' . $link . '" method="POST">' .
                        '<div class="rmenu">' .
                        '<input type="submit" name="deltopic" value="' . _t('Delete all') . '" />' .
                        '</div></form>';
                }
            } else {
                echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
            }

            echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

            if ($total > $user->config->kmess) {
                echo '<div class="topmenu">' . $tools->displayPagination('?act=forum&amp;mod=htopics&amp;', $start, $total, $user->config->kmess) . '</div>' .
                    '<p><form action="?act=forum&amp;mod=htopics" method="post">' .
                    '<input type="text" name="page" size="2"/>' .
                    '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
                    '</form></p>';
            }
        }
        break;

    case 'hposts':
        // Управление скрытыми постави форума
        echo '<div class="phdr"><a href="?act=forum"><b>' . _t('Forum Management') . '</b></a> | ' . _t('Hidden posts') . '</div>';
        $sort = '';
        $link = '';

        if (isset($_GET['tsort'])) {
            $sort = " AND `forum_messages`.`topic_id` = '" . abs((int) ($_GET['tsort'])) . "'";
            $link = '&amp;tsort=' . abs((int) ($_GET['tsort']));
            echo '<div class="bmenu">' . _t('Filter by topic') . ' <a href="?act=forum&amp;mod=hposts">[x]</a></div>';
        } elseif (isset($_GET['usort'])) {
            $sort = " AND `forum_messages`.`user_id` = '" . abs((int) ($_GET['usort'])) . "'";
            $link = '&amp;usort=' . abs((int) ($_GET['usort']));
            echo '<div class="bmenu">' . _t('Filter by author') . ' <a href="?act=forum&amp;mod=hposts">[x]</a></div>';
        }

        if (isset($_POST['delpost'])) {
            if ($user->rights != 9) {
                echo $tools->displayError(_t('Access forbidden'));
                echo $view->render('system::app/old_content', ['content' => ob_get_clean()]);
                exit;
            }

            $req = $db->query("SELECT `id` FROM `forum_messages` WHERE `deleted` = '1' ${sort}");

            while ($res = $req->fetch()) {
                $req_f = $db->query("SELECT * FROM `cms_forum_files` WHERE `post` = '" . $res['id'] . "'");

                if ($req_f->rowCount()) {
                    while ($res_f = $req_f->fetch()) {
                        // Удаляем файлы
                        unlink('../files/forum/attach/' . $res_f['filename']);
                    }
                    $db->exec("DELETE FROM `cms_forum_files` WHERE `post` = '" . $res['id'] . "'");
                }
            }

            // Удаляем посты
            $db->exec("DELETE FROM `forum_messages` WHERE `deleted` = '1' ${sort}");

            header('Location: ?act=forum&mod=hposts');
        } else {
            $total = $db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `deleted` = '1' ${sort}")->fetchColumn();

            if ($total > $user->config->kmess) {
                echo '<div class="topmenu">' . $tools->displayPagination('?act=forum&amp;mod=hposts&amp;', $start, $total, $user->config->kmess) . '</div>';
            }

            $req = $db->query("SELECT `forum_messages`.*, 
            `forum_messages`.`id` AS `fid`, 
            `forum_messages`.`user_id` AS `id`, 
            `forum_messages`.`user_name` AS `name`, 
            `forum_messages`.`user_agent` AS `browser`,
            `users`.`rights`, 
            `users`.`lastdate`, 
            `users`.`sex`, 
            `users`.`status`, 
            `users`.`datereg`
            FROM `forum_messages` LEFT JOIN `users` ON `forum_messages`.`user_id` = `users`.`id`
            WHERE `forum_messages`.`deleted` = '1' ${sort} ORDER BY `forum_messages`.`id` DESC LIMIT " . $start . ',' . $user->config->kmess);

            if ($req->rowCount()) {
                $i = 0;

                while ($res = $req->fetch()) {
                    $posttime = ' <span class="gray">(' . $tools->displayDate($res['time']) . ')</span>';
                    $page = ceil($db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id` = '" . $res['topic_id'] . "' AND `id` " . ($set_forum['upfp'] ? '>=' : '<=') . " '" . $res['fid'] . "'")->fetchColumn() / $user->config->kmess);
                    $text = mb_substr($res['text'], 0, 500);
                    $text = $tools->checkout($text, 1, 0);
                    $text = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $text);
                    $theme = $db->query("SELECT `id`, `name` FROM `forum_topic` WHERE `id` = '" . $res['topic_id'] . "'")->fetch();
                    $text = '<b>' . $theme['name'] . '</b> <a href="../forum/?type=topic&id=' . $theme['id'] . '&amp;page=' . $page . '">&gt;&gt;</a><br>' . $text;
                    $subtext = '<span class="gray">' . _t('Filter') . ':</span> ';
                    $subtext .= '<a href="?act=forum&amp;mod=hposts&amp;tsort=' . $theme['id'] . '">' . _t('by topic') . '</a> | ';
                    $subtext .= '<a href="?act=forum&amp;mod=hposts&amp;usort=' . $res['user_id'] . '">' . _t('by author') . '</a>';
                    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                    echo $tools->displayUser($res, [
                        'header' => $posttime,
                        'body'   => $text,
                        'sub'    => $subtext,
                    ]);
                    echo '</div>';
                    ++$i;
                }

                if ($user->rights == 9) {
                    echo '<form action="?act=forum&amp;mod=hposts' . $link . '" method="POST"><div class="rmenu"><input type="submit" name="delpost" value="' . _t('Delete all') . '" /></div></form>';
                }
            } else {
                echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
            }

            echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

            if ($total > $user->config->kmess) {
                echo '<div class="topmenu">' . $tools->displayPagination('?act=forum&amp;mod=hposts&amp;', $start, $total, $user->config->kmess) . '</div>' .
                    '<p><form action="?act=forum&amp;mod=hposts" method="post">' .
                    '<input type="text" name="page" size="2"/>' .
                    '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
                    '</form></p>';
            }
        }
        break;

    default:
        // Панель управления форумом
        $total_cat = $db->query('SELECT COUNT(*) FROM `forum_sections` WHERE `section_type` != 1 OR `section_type` IS NULL')->fetchColumn();
        $total_sub = $db->query('SELECT COUNT(*) FROM `forum_sections` WHERE `section_type` = 1')->fetchColumn();
        $total_thm = $db->query('SELECT COUNT(*) FROM `forum_topic`')->fetchColumn();
        $total_thm_del = $db->query('SELECT COUNT(*) FROM `forum_topic` WHERE `deleted` = 1')->fetchColumn();
        $total_msg = $db->query('SELECT COUNT(*) FROM `forum_messages`')->fetchColumn();
        $total_msg_del = $db->query('SELECT COUNT(*) FROM `forum_messages` WHERE `deleted` = 1')->fetchColumn();
        $total_files = $db->query('SELECT COUNT(*) FROM `cms_forum_files`')->fetchColumn();
        $total_votes = $db->query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type` = '1'")->fetchColumn();

        echo '<div class="phdr"><a href="./"><b>' . _t('Admin Panel') . '</b></a> | ' . _t('Forum Management') . '</div>' .
            '<div class="gmenu"><p><h3>' . _t('Statistic') . '</h3><ul>' .
            '<li>' . _t('Categories') . ':&#160;' . $total_cat . '</li>' .
            '<li>' . _t('Sections') . ':&#160;' . $total_sub . '</li>' .
            '<li>' . _t('Topics') . ':&#160;' . $total_thm . '&#160;/&#160;<span class="red">' . $total_thm_del . '</span></li>' .
            '<li>' . _t('Messages') . ':&#160;' . $total_msg . '&#160;/&#160;<span class="red">' . $total_msg_del . '</span></li>' .
            '<li>' . _t('Files') . ':&#160;' . $total_files . '</li>' .
            '<li>' . _t('Votes') . ':&#160;' . $total_votes . '</li>' .
            '</ul></p></div>' .
            '<div class="menu"><p><h3>' . _t('Settings') . '</h3><ul>' .
            '<li><a href="?act=forum&amp;mod=cat"><b>' . _t('Forum structure') . '</b></a></li>' .
            '<li><a href="?act=forum&amp;mod=hposts">' . _t('Hidden posts') . '</a> (' . $total_msg_del . ')</li>' .
            '<li><a href="?act=forum&amp;mod=htopics">' . _t('Hidden topics') . '</a> (' . $total_thm_del . ')</li>' .
            '</ul></p></div>' .
            '<div class="phdr"><a href="../forum/">' . _t('Go to Forum') . '</a></div>';
}

echo '<p><a href="./">' . _t('Admin Panel') . '</a></p>';

echo $view->render('system::app/old_content', [
    'title'   => _t('Admin Panel'),
    'content' => ob_get_clean(),
]);
