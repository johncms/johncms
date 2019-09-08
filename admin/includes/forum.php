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

defined('_IN_JOHNADM') or die('Error: restricted access');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

// Проверяем права доступа
if ($systemUser->rights < 7) {
    header('Location: http://johncms.com/?err');
    exit;
}

// Задаем пользовательские настройки форума
$set_forum = unserialize($systemUser->set_forum);

if (!isset($set_forum) || empty($set_forum)) {
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
        if (!$id) {
            echo $tools->displayError(_t('Wrong data'), '<a href="index.php?act=forum">' . _t('Forum Management') . '</a>');
            require('../system/end.php');
            exit;
        }

        $req = $db->query("SELECT * FROM `forum` WHERE `id` = '$id' AND (`type` = 'f' OR `type` = 'r')");

        if ($req->rowCount()) {
            $res = $req->fetch();
            echo '<div class="phdr"><b>' . ($res['type'] == 'r' ? _t('Delete section') : _t('Delete category')) . ':</b> ' . $res['text'] . '</div>';
            // Проверяем, есть ли подчиненная информация
            $total = $db->query("SELECT COUNT(*) FROM `forum` WHERE `refid` = '$id' AND (`type` = 'f' OR `type` = 'r' OR `type` = 't')")->fetchColumn();

            if ($total) {
                if ($res['type'] == 'f') {
                    // Удаление категории с подчиненными данными
                    if (isset($_POST['submit'])) {
                        $category = isset($_POST['category']) ? intval($_POST['category']) : 0;

                        if (!$category || $category == $id) {
                            echo $tools->displayError(_t('Wrong data'));
                            require('../system/end.php');
                            exit;
                        }

                        $check = $db->query("SELECT COUNT(*) FROM `forum` WHERE `id` = '$category' AND `type` = 'f'")->fetchColumn();

                        if (!$check) {
                            echo $tools->displayError(_t('Wrong data'));
                            require('../system/end.php');
                            exit;
                        }

                        // Вычисляем правила сортировки и перемещаем разделы
                        $sort = $db->query("SELECT * FROM `forum` WHERE `refid` = '$category' AND `type` ='r' ORDER BY `realid` DESC")->fetch();
                        $sortnum = !empty($sort['realid']) && $sort['realid'] > 0 ? $sort['realid'] + 1 : 1;
                        $req_c = $db->query("SELECT * FROM `forum` WHERE `refid` = '$id' AND `type` = 'r'");

                        while ($res_c = $req_c->fetch()) {
                            $db->exec("UPDATE `forum` SET `refid` = '" . $category . "', `realid` = '$sortnum' WHERE `id` = " . $res_c['id']);
                            ++$sortnum;
                        }

                        // Перемещаем файлы в выбранную категорию
                        $db->exec("UPDATE `cms_forum_files` SET `cat` = '" . $category . "' WHERE `cat` = " . $res['refid']);
                        $db->exec("DELETE FROM `forum` WHERE `id` = '$id'");
                        echo '<div class="rmenu"><p><h3>' . _t('Category deleted') . '</h3>' . _t('All content has been moved to') . ' <a href="../forum/index.php?id=' . $category . '">' . _t('selected category') . '</a></p></div>';
                    } else {
                        echo '<form action="index.php?act=forum&amp;mod=del&amp;id=' . $id . '" method="POST">' .
                            '<div class="rmenu"><p>' . _t('<h3>WARNING!</h3>There are subsections. Move them to another category.') . '</p>' .
                            '<p><h3>' . _t('Select category') . '</h3><select name="category" size="1">';
                        $req_c = $db->query("SELECT * FROM `forum` WHERE `type` = 'f' AND `id` != '$id' ORDER BY `realid` ASC");

                        while ($res_c = $req_c->fetch()) {
                            echo '<option value="' . $res_c['id'] . '">' . $res_c['text'] . '</option>';
                        }

                        echo '</select><br><small>' . _t('All categories, topics, and files will be moved into selected category. Old category will be removed.') . '</small></p>' .
                            '<p><input type="submit" name="submit" value="' . _t('Move') . '" /></p></div>';

                        // Для супервайзоров запрос на полное удаление
                        if ($systemUser->rights == 9) {
                            echo '<div class="rmenu"><p><h3>' . _t('Complete removal') . '</h3>' . _t('If you want to destroy all the information, first remove') . ' <a href="index.php?act=forum&amp;mod=cat&amp;id=' . $id . '">' . _t('subsections') . '</a></p></div>';
                        }

                        echo '</form>';
                    }
                } else {
                    // Удаление раздела с подчиненными данными
                    if (isset($_POST['submit'])) {
                        // Предварительные проверки
                        $subcat = isset($_POST['subcat']) ? intval($_POST['subcat']) : 0;

                        if (!$subcat || $subcat == $id) {
                            echo $tools->displayError(_t('Wrong data'), '<a href="index.php?act=forum">' . _t('Forum Management') . '</a>');
                            require('../system/end.php');
                            exit;
                        }

                        $check = $db->query("SELECT COUNT(*) FROM `forum` WHERE `id` = '$subcat' AND `type` = 'r'")->fetchColumn();

                        if (!$check) {
                            echo $tools->displayError(_t('Wrong data'), '<a href="index.php?act=forum">' . _t('Forum Management') . '</a>');
                            require('../system/end.php');
                            exit;
                        }

                        $db->exec("UPDATE `forum` SET `refid` = '$subcat' WHERE `refid` = '$id'");
                        $db->exec("UPDATE `cms_forum_files` SET `subcat` = '$subcat' WHERE `subcat` = '$id'");
                        $db->exec("DELETE FROM `forum` WHERE `id` = '$id'");
                        echo '<div class="rmenu"><p><h3>' . _t('Section deleted') . '</h3>' . _t('All content has been moved to') . ' <a href="../forum/index.php?id=' . $subcat . '">' . _t('selected section') . '</a>.' .
                            '</p></div>';
                    } elseif (isset($_POST['delete'])) {
                        if ($systemUser->rights != 9) {
                            echo $tools->displayError(_t('Access forbidden'));
                            require_once('../system/end.php');
                            exit;
                        }

                        // Удаляем файлы
                        $req_f = $db->query("SELECT * FROM `cms_forum_files` WHERE `subcat` = '$id'");

                        while ($res_f = $req_f->fetch()) {
                            unlink('../files/forum/attach/' . $res_f['filename']);
                        }

                        $db->exec("DELETE FROM `cms_forum_files` WHERE `subcat` = '$id'");

                        // Удаляем посты, голосования и метки прочтений
                        $req_t = $db->query("SELECT `id` FROM `forum` WHERE `refid` = '$id' AND `type` = 't'");

                        while ($res_t = $req_t->fetch()) {
                            $db->exec("DELETE FROM `forum` WHERE `refid` = '" . $res_t['id'] . "'");
                            $db->exec("DELETE FROM `cms_forum_vote` WHERE `topic` = '" . $res_t['id'] . "'");
                            $db->exec("DELETE FROM `cms_forum_vote_users` WHERE `topic` = '" . $res_t['id'] . "'");
                            $db->exec("DELETE FROM `cms_forum_rdm` WHERE `topic_id` = '" . $res_t['id'] . "'");
                        }

                        // Удаляем темы
                        $db->exec("DELETE FROM `forum` WHERE `refid` = '$id'");
                        // Удаляем раздел
                        $db->exec("DELETE FROM `forum` WHERE `id` = '$id'");
                        // Оптимизируем таблицы
                        $db->query("OPTIMIZE TABLE `cms_forum_files` , `cms_forum_rdm` , `forum` , `cms_forum_vote` , `cms_forum_vote_users`");
                        echo '<div class="rmenu"><p>' . _t('Section with all contents are removed') . '<br>' .
                            '<a href="index.php?act=forum&amp;mod=cat&amp;id=' . $res['refid'] . '">' . _t('Go to category') . '</a></p></div>';
                    } else {
                        echo '<form action="index.php?act=forum&amp;mod=del&amp;id=' . $id . '" method="POST"><div class="rmenu">' .
                            '<p>' . _t('<h3>WARNING!</h3>There are topics in the section. You must move them to another section.') . '</p>' . '<p><h3>' . _t('Select section') . '</h3>';
                        $cat = isset($_GET['cat']) ? abs(intval($_GET['cat'])) : 0;
                        $ref = $cat ? $cat : $res['refid'];
                        $req_r = $db->query("SELECT * FROM `forum` WHERE `refid` = '$ref' AND `id` != '$id' AND `type` = 'r' ORDER BY `realid` ASC");

                        while ($res_r = $req_r->fetch()) {
                            echo '<input type="radio" name="subcat" value="' . $res_r['id'] . '" />&#160;' . $res_r['text'] . '<br>';
                        }

                        echo '</p><p><h3>' . _t('Other category') . '</h3><ul>';
                        $req_c = $db->query("SELECT * FROM `forum` WHERE `type` = 'f' AND `id` != '$ref' ORDER BY `realid` ASC");

                        while ($res_c = $req_c->fetch()) {
                            echo '<li><a href="index.php?act=forum&amp;mod=del&amp;id=' . $id . '&amp;cat=' . $res_c['id'] . '">' . $res_c['text'] . '</a></li>';
                        }

                        echo '</ul><small>' . _t('All the topics and files will be moved to selected section. Old section will be deleted.') . '</small></p><p><input type="submit" name="submit" value="' . _t('Move') . '" /></p></div>';

                        if ($systemUser->rights == 9) {
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
                    $db->exec("DELETE FROM `forum` WHERE `id` = '$id'");
                    echo '<div class="rmenu"><p>' . ($res['type'] == 'r' ? _t('Section deleted') : _t('Category deleted')) . '</p></div>';
                } else {
                    echo '<div class="rmenu"><p>' . _t('Do you really want to delete?') . '</p>' .
                        '<p><form action="index.php?act=forum&amp;mod=del&amp;id=' . $id . '" method="POST">' .
                        '<input type="submit" name="submit" value="' . _t('Delete') . '" />' .
                        '</form></p></div>';
                }
            }
            echo '<div class="phdr"><a href="index.php?act=forum&amp;mod=cat">' . _t('Back') . '</a></div>';
        } else {
            header('Location: index.php?act=forum&mod=cat');
        }

        break;

    case 'add':
        // Добавление категории
        if ($id) {
            // Проверяем наличие категории
            $req = $db->query("SELECT `text` FROM `forum` WHERE `id` = '$id' AND `type` = 'f'");

            if ($req->rowCount()) {
                $res = $req->fetch();
                $cat_name = $res['text'];
            } else {
                echo $tools->displayError(_t('Wrong data'), '<a href="index.php?act=forum">' . _t('Forum Management') . '</a>');
                require('../system/end.php');
                exit;
            }
        }

        if (isset($_POST['submit'])) {
            // Принимаем данные
            $name = isset($_POST['name']) ? trim($_POST['name']) : '';
            $desc = isset($_POST['desc']) ? trim($_POST['desc']) : '';
            $allow = isset($_POST['allow']) ? intval($_POST['allow']) : 0;

            // Проверяем на ошибки
            $error = [];

            if (!$name) {
                $error[] = _t('You have not entered Title');
            }

            if ($name && (mb_strlen($name) < 2 || mb_strlen($name) > 30)) {
                $error[] = _t('Title') . ': ' . _t('Invalid length');
            }

            if ($desc && mb_strlen($desc) < 2) {
                $error[] = _t('Description should be at least 2 characters in length');
            }

            if (!$error) {
                // Добавляем в базу категорию
                $req = $db->query("SELECT `realid` FROM `forum` WHERE " . ($id ? "`refid` = '$id' AND `type` = 'r'" : "`type` = 'f'") . " ORDER BY `realid` DESC LIMIT 1");

                if ($req->rowCount()) {
                    $res = $req->fetch();
                    $sort = $res['realid'] + 1;
                } else {
                    $sort = 1;
                }

                $db->prepare('
                  INSERT INTO `forum` SET
                  `refid` = ?,
                  `type` = ?,
                  `text` = ?,
                  `soft` = ?,
                  `edit` = ?,
                  `curators` = \'\',
                  `realid` = ?
                ')->execute([
                    ($id ? $id : 0),
                    ($id ? 'r' : 'f'),
                    $name,
                    $desc,
                    $allow,
                    $sort,
                ]);

                header('Location: index.php?act=forum&mod=cat' . ($id ? '&id=' . $id : ''));
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

            echo '<form action="index.php?act=forum&amp;mod=add' . ($id ? '&amp;id=' . $id : '') . '" method="post">' .
                '<div class="gmenu">' .
                '<p><h3>' . _t('Title') . '</h3>' .
                '<input type="text" name="name" />' .
                '<br><small>' . _t('Min. 2, Max. 30 characters') . '</small></p>' .
                '<p><h3>' . _t('Description') . '</h3>' .
                '<textarea name="desc" rows="' . $systemUser->getConfig()->fieldHeight . '"></textarea>' .
                '<br><small>' . _t('Optional field') . '<br>' . _t('Min. 2, Max. 500 characters') . '</small></p>';

            if ($id) {
                echo '<p><input type="radio" name="allow" value="0" checked="checked"/>&#160;' . _t('Common access') . '<br>' .
                    '<input type="radio" name="allow" value="4"/>&#160;' . _t('Only for reading') . '<br>' .
                    '<input type="radio" name="allow" value="2"/>&#160;' . _t('Allow authors to edit the 1st post') . '<br>' .
                    '<input type="radio" name="allow" value="1"/>&#160;' . _t('Assign the newly created authors as curators') . '</p>';
            }

            echo '<p><input type="submit" value="' . _t('Add') . '" name="submit" />' .
                '</p></div></form>' .
                '<div class="phdr"><a href="index.php?act=forum&amp;mod=cat' . ($id ? '&amp;id=' . $id : '') . '">' . _t('Back') . '</a></div>';
        }
        break;

    case 'edit':
        // Редактирование выбранной категории, или раздела
        if (!$id) {
            echo $tools->displayError(_t('Wrong data'), '<a href="index.php?act=forum">' . _t('Forum Management') . '</a>');
            require('../system/end.php');
            exit;
        }

        $req = $db->query("SELECT * FROM `forum` WHERE `id` = '$id'");

        if ($req->rowCount()) {
            $res = $req->fetch();

            if ($res['type'] == 'f' || $res['type'] == 'r') {
                if (isset($_POST['submit'])) {
                    // Принимаем данные
                    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
                    $desc = isset($_POST['desc']) ? trim($_POST['desc']) : '';
                    $category = isset($_POST['category']) ? intval($_POST['category']) : 0;
                    $allow = isset($_POST['allow']) ? intval($_POST['allow']) : 0;

                    // проверяем на ошибки
                    $error = [];

                    if ($res['type'] == 'r' && !$category) {
                        $error[] = _t('Category is not chosen');
                    } elseif ($res['type'] == 'r' && !$db->query("SELECT COUNT(*) FROM `forum` WHERE `id` = '$category' AND `type` = 'f'")->fetchColumn()) {
                        $error[] = _t('Category is not chosen');
                    }

                    if (!$name) {
                        $error[] = _t('You have not entered Title');
                    }

                    if ($name && (mb_strlen($name) < 2 || mb_strlen($name) > 30)) {
                        $error[] = _t('Title') . ': ' . _t('Invalid length');
                    }

                    if ($desc && mb_strlen($desc) < 2) {
                        $error[] = _t('Description should be at least 2 characters in length');
                    }

                    if (!$error) {
                        // Записываем в базу
                        $db->prepare('
                          UPDATE `forum` SET
                          `text` = ?,
                          `soft` = ?,
                          `edit` = ?
                          WHERE `id` = ?
                        ')->execute([
                            $name,
                            $desc,
                            $allow,
                            $id,
                        ]);

                        if ($res['type'] == 'r' && $category != $res['refid']) {
                            // Вычисляем сортировку
                            $req_s = $db->query("SELECT `realid` FROM `forum` WHERE `refid` = '$category' AND `type` = 'r' ORDER BY `realid` DESC LIMIT 1");
                            $res_s = $req_s->fetch();
                            $sort = $res_s['realid'] + 1;
                            // Меняем категорию
                            $db->exec("UPDATE `forum` SET `refid` = '$category', `realid` = '$sort' WHERE `id` = '$id'");
                            // Меняем категорию для прикрепленных файлов
                            $db->exec("UPDATE `cms_forum_files` SET `cat` = '$category' WHERE `cat` = '" . $res['refid'] . "'");
                        }
                        header('Location: index.php?act=forum&mod=cat' . ($res['type'] == 'r' ? '&id=' . $res['refid'] : ''));
                    } else {
                        // Выводим сообщение об ошибках
                        echo $tools->displayError($error);
                    }
                } else {
                    // Форма ввода
                    echo '<div class="phdr"><b>' . ($res['type'] == 'r' ? _t('Edit Section') : _t('Edit Category')) . '</b></div>' .
                        '<form action="index.php?act=forum&amp;mod=edit&amp;id=' . $id . '" method="post">' .
                        '<div class="gmenu">' .
                        '<p><h3>' . _t('Title') . '</h3>' .
                        '<input type="text" name="name" value="' . $res['text'] . '"/>' .
                        '<br><small>' . _t('Min. 2, Max. 30 characters') . '</small></p>' .
                        '<p><h3>' . _t('Description') . '</h3>' .
                        '<textarea name="desc" rows="' . $systemUser->getConfig()->fieldHeight . '">' . str_replace('<br>', "\r\n", $res['soft']) . '</textarea>' .
                        '<br><small>' . _t('Optional field') . '<br>' . _t('Min. 2, Max. 500 characters') . '</small></p>';

                    if ($res['type'] == 'r') {
                        $allow = !empty($res['edit']) ? intval($res['edit']) : 0;
                        echo '<p><input type="radio" name="allow" value="0" ' . (!$allow ? 'checked="checked"' : '') . '/>&#160;' . _t('Common access') . '<br>' .
                            '<input type="radio" name="allow" value="4" ' . ($allow == 4 ? 'checked="checked"' : '') . '/>&#160;' . _t('Only for reading') . '<br>' .
                            '<input type="radio" name="allow" value="2" ' . ($allow == 2 ? 'checked="checked"' : '') . '/>&#160;' . _t('Allow authors to edit the 1st post') . '<br>' .
                            '<input type="radio" name="allow" value="1" ' . ($allow == 1 ? 'checked="checked"' : '') . '/>&#160;' . _t('Assign the newly created authors as curators') . '</p>';
                        echo '<p><h3>' . _t('Category') . '</h3><select name="category" size="1">';

                        $req_c = $db->query("SELECT * FROM `forum` WHERE `type` = 'f' ORDER BY `realid` ASC");

                        while ($res_c = $req_c->fetch()) {
                            echo '<option value="' . $res_c['id'] . '"' . ($res_c['id'] == $res['refid'] ? ' selected="selected"' : '') . '>' . $res_c['text'] . '</option>';
                        }
                        echo '</select></p>';
                    }
                    echo '<p><input type="submit" value="' . _t('Save') . '" name="submit" />' .
                        '</p></div></form>' .
                        '<div class="phdr"><a href="index.php?act=forum&amp;mod=cat' . ($res['type'] == 'r' ? '&amp;id=' . $res['refid'] : '') . '">' . _t('Back') . '</a></div>';
                }
            } else {
                header('Location: index.php?act=forum&mod=cat');
            }
        } else {
            header('Location: index.php?act=forum&mod=cat');
        }
        break;

    case 'up':
        // Перемещение на одну позицию вверх
        if ($id) {
            $req = $db->query("SELECT * FROM `forum` WHERE `id` = '$id'");

            if ($req->rowCount()) {
                $res1 = $req->fetch();
                $sort = $res1['realid'];
                $req = $db->query("SELECT * FROM `forum` WHERE `type` = '" . ($res1['type'] == 'f' ? 'f' : 'r') . "' AND `realid` < '$sort' ORDER BY `realid` DESC LIMIT 1");

                if ($req->rowCount()) {
                    $res = $req->fetch();
                    $id2 = $res['id'];
                    $sort2 = $res['realid'];
                    $db->exec("UPDATE `forum` SET `realid` = '$sort2' WHERE `id` = '$id'");
                    $db->exec("UPDATE `forum` SET `realid` = '$sort' WHERE `id` = '$id2'");
                }
            }
        }

        header('Location: index.php?act=forum&mod=cat' . ($res1['type'] == 'r' ? '&id=' . $res1['refid'] : ''));
        break;

    case 'down':
        // Перемещение на одну позицию вниз
        if ($id) {
            $req = $db->query("SELECT * FROM `forum` WHERE `id` = '$id'");

            if ($req->rowCount()) {
                $res1 = $req->fetch();
                $sort = $res1['realid'];
                $req = $db->query("SELECT * FROM `forum` WHERE `type` = '" . ($res1['type'] == 'f' ? 'f' : 'r') . "' AND `realid` > '$sort' ORDER BY `realid` ASC LIMIT 1");

                if ($req->rowCount()) {
                    $res = $req->fetch();
                    $id2 = $res['id'];
                    $sort2 = $res['realid'];
                    $db->exec("UPDATE `forum` SET `realid` = '$sort2' WHERE `id` = '$id'");
                    $db->exec("UPDATE `forum` SET `realid` = '$sort' WHERE `id` = '$id2'");
                }
            }
        }
        header('Location: index.php?act=forum&mod=cat' . ($res1['type'] == 'r' ? '&id=' . $res1['refid'] : ''));
        break;

    case 'cat':
        // Управление категориями и разделами
        echo '<div class="phdr"><a href="index.php?act=forum"><b>' . _t('Forum Management') . '</b></a> | ' . _t('Forum structure') . '</div>';

        if ($id) {
            // Управление разделами
            $req = $db->query("SELECT `text` FROM `forum` WHERE `id` = '$id' AND `type` = 'f'");
            $res = $req->fetch();
            echo '<div class="bmenu"><a href="index.php?act=forum&amp;mod=cat"><b>' . $res['text'] . '</b></a> | ' . _t('List of sections') . '</div>';
            $req = $db->query("SELECT * FROM `forum` WHERE `refid` = '$id' AND `type` = 'r' ORDER BY `realid` ASC");

            if ($req->rowCount()) {
                $i = 0;

                while ($res = $req->fetch()) {
                    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                    echo '<b>' . $res['text'] . '</b>' .
                        '&#160;<a href="../forum/index.php?id=' . $res['id'] . '">&gt;&gt;</a>';

                    if (!empty($res['soft'])) {
                        echo '<br><span class="gray"><small>' . $res['soft'] . '</small></span><br>';
                    }

                    echo '<div class="sub">' .
                        '<a href="index.php?act=forum&amp;mod=up&amp;id=' . $res['id'] . '">' . _t('Up') . '</a> | ' .
                        '<a href="index.php?act=forum&amp;mod=down&amp;id=' . $res['id'] . '">' . _t('Down') . '</a> | ' .
                        '<a href="index.php?act=forum&amp;mod=edit&amp;id=' . $res['id'] . '">' . _t('Edit') . '</a> | ' .
                        '<a href="index.php?act=forum&amp;mod=del&amp;id=' . $res['id'] . '">' . _t('Delete') . '</a>' .
                        '</div></div>';
                    ++$i;
                }
            } else {
                echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
            }
        } else {
            // Управление категориями
            echo '<div class="bmenu">' . _t('List of categories') . '</div>';
            $req = $db->query("SELECT * FROM `forum` WHERE `type` = 'f' ORDER BY `realid` ASC");
            $i = 0;

            while ($res = $req->fetch()) {
                echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                echo '<a href="index.php?act=forum&amp;mod=cat&amp;id=' . $res['id'] . '"><b>' . $res['text'] . '</b></a> ' .
                    '(' . $db->query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'r' AND `refid` = '" . $res['id'] . "'")->fetchColumn() . ')' .
                    '&#160;<a href="../forum/index.php?id=' . $res['id'] . '">&gt;&gt;</a>';

                if (!empty($res['soft'])) {
                    echo '<br><span class="gray"><small>' . $res['soft'] . '</small></span><br>';
                }

                echo '<div class="sub">' .
                    '<a href="index.php?act=forum&amp;mod=up&amp;id=' . $res['id'] . '">' . _t('Up') . '</a> | ' .
                    '<a href="index.php?act=forum&amp;mod=down&amp;id=' . $res['id'] . '">' . _t('Down') . '</a> | ' .
                    '<a href="index.php?act=forum&amp;mod=edit&amp;id=' . $res['id'] . '">' . _t('Edit') . '</a> | ' .
                    '<a href="index.php?act=forum&amp;mod=del&amp;id=' . $res['id'] . '">' . _t('Delete') . '</a>' .
                    '</div></div>';
                ++$i;
            }
        }

        echo '<div class="gmenu">' .
            '<form action="index.php?act=forum&amp;mod=add' . ($id ? '&amp;id=' . $id : '') . '" method="post">' .
            '<input type="submit" value="' . _t('Add') . '" />' .
            '</form></div>' .
            '<div class="phdr">' . ($mod == 'cat' && $id ? '<a href="index.php?act=forum&amp;mod=cat">' . _t('List of categories') . '</a>' : '<a href="index.php?act=forum">' . _t('Forum Management') . '</a>') . '</div>';
        break;

    case 'htopics':
        // Управление скрытыми темами форума
        echo '<div class="phdr"><a href="index.php?act=forum"><b>' . _t('Forum Management') . '</b></a> | ' . _t('Hidden topics') . '</div>';
        $sort = '';
        $link = '';

        if (isset($_GET['usort'])) {
            $sort = " AND `forum`.`user_id` = '" . abs(intval($_GET['usort'])) . "'";
            $link = '&amp;usort=' . abs(intval($_GET['usort']));
            echo '<div class="bmenu">' . _t('Filter by author') . ' <a href="index.php?act=forum&amp;mod=htopics">[x]</a></div>';
        }

        if (isset($_GET['rsort'])) {
            $sort = " AND `forum`.`refid` = '" . abs(intval($_GET['rsort'])) . "'";
            $link = '&amp;rsort=' . abs(intval($_GET['rsort']));
            echo '<div class="bmenu">' . _t('Filter by section') . ' <a href="index.php?act=forum&amp;mod=htopics">[x]</a></div>';
        }

        if (isset($_POST['deltopic'])) {
            if ($systemUser->rights != 9) {
                echo $tools->displayError(_t('Access forbidden'));
                require('../system/end.php');
                exit;
            }

            $req = $db->query("SELECT `id` FROM `forum` WHERE `type` = 't' AND `close` = '1' $sort");

            while ($res = $req->fetch()) {
                $req_f = $db->query("SELECT * FROM `cms_forum_files` WHERE `topic` = " . $res['id']);

                if ($req_f->rowCount()) {
                    // Удаляем файлы
                    while ($res_f = $req_f->fetch()) {
                        unlink('../files/forum/attach/' . $res_f['filename']);
                    }
                    $db->exec("DELETE FROM `cms_forum_files` WHERE `topic` = " . $res['id']);
                }
                // Удаляем посты
                $db->exec("DELETE FROM `forum` WHERE `type` = 'm' AND `refid` = " . $res['id']);
            }
            // Удаляем темы
            $db->exec("DELETE FROM `forum` WHERE `type` = 't' AND `close` = '1' $sort");

            header('Location: index.php?act=forum&mod=htopics');
        } else {
            $total = $db->query("SELECT COUNT(*) FROM `forum` WHERE `type` = 't' AND `close` = '1' $sort")->fetchColumn();

            if ($total > $kmess) {
                echo '<div class="topmenu">' . $tools->displayPagination('index.php?act=forum&amp;mod=htopics&amp;', $start, $total, $kmess) . '</div>';
            }

            $req = $db->query("SELECT `forum`.*, `forum`.`id` AS `fid`, `forum`.`user_id` AS `id`, `forum`.`from` AS `name`, `forum`.`soft` AS `browser`, `users`.`rights`, `users`.`lastdate`, `users`.`sex`, `users`.`status`, `users`.`datereg`
            FROM `forum` LEFT JOIN `users` ON `forum`.`user_id` = `users`.`id`
            WHERE `forum`.`type` = 't' AND `forum`.`close` = '1' $sort ORDER BY `forum`.`id` DESC LIMIT $start, $kmess");

            if ($req->rowCount()) {
                $i = 0;

                while ($res = $req->fetch()) {
                    $subcat = $db->query("SELECT * FROM `forum` WHERE `id` = '" . $res['refid'] . "'")->fetch();
                    $cat = $db->query("SELECT * FROM `forum` WHERE `id` = '" . $subcat['refid'] . "'")->fetch();
                    $ttime = '<span class="gray">(' . $tools->displayDate($res['time']) . ')</span>';
                    $text = '<a href="../forum/index.php?id=' . $res['fid'] . '"><b>' . $res['text'] . '</b></a>';
                    $text .= '<br><small><a href="../forum/index.php?id=' . $cat['id'] . '">' . $cat['text'] . '</a> / <a href="../forum/index.php?id=' . $subcat['id'] . '">' . $subcat['text'] . '</a></small>';
                    $subtext = '<span class="gray">' . _t('Filter') . ':</span> ';
                    $subtext .= '<a href="index.php?act=forum&amp;mod=htopics&amp;rsort=' . $res['refid'] . '">' . _t('by section') . '</a> | ';
                    $subtext .= '<a href="index.php?act=forum&amp;mod=htopics&amp;usort=' . $res['user_id'] . '">' . _t('by author') . '</a>';
                    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                    echo $tools->displayUser($res, [
                        'header' => $ttime,
                        'body'   => $text,
                        'sub'    => $subtext,
                    ]);
                    echo '</div>';
                    ++$i;
                }

                if ($systemUser->rights == 9) {
                    echo '<form action="index.php?act=forum&amp;mod=htopics' . $link . '" method="POST">' .
                        '<div class="rmenu">' .
                        '<input type="submit" name="deltopic" value="' . _t('Delete all') . '" />' .
                        '</div></form>';
                }
            } else {
                echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
            }

            echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

            if ($total > $kmess) {
                echo '<div class="topmenu">' . $tools->displayPagination('index.php?act=forum&amp;mod=htopics&amp;', $start, $total, $kmess) . '</div>' .
                    '<p><form action="index.php?act=forum&amp;mod=htopics" method="post">' .
                    '<input type="text" name="page" size="2"/>' .
                    '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
                    '</form></p>';
            }
        }
        break;

    case 'hposts':
        // Управление скрытыми постави форума
        echo '<div class="phdr"><a href="index.php?act=forum"><b>' . _t('Forum Management') . '</b></a> | ' . _t('Hidden posts') . '</div>';
        $sort = '';
        $link = '';

        if (isset($_GET['tsort'])) {
            $sort = " AND `forum`.`refid` = '" . abs(intval($_GET['tsort'])) . "'";
            $link = '&amp;tsort=' . abs(intval($_GET['tsort']));
            echo '<div class="bmenu">' . _t('Filter by topic') . ' <a href="index.php?act=forum&amp;mod=hposts">[x]</a></div>';
        } elseif (isset($_GET['usort'])) {
            $sort = " AND `forum`.`user_id` = '" . abs(intval($_GET['usort'])) . "'";
            $link = '&amp;usort=' . abs(intval($_GET['usort']));
            echo '<div class="bmenu">' . _t('Filter by author') . ' <a href="index.php?act=forum&amp;mod=hposts">[x]</a></div>';
        }

        if (isset($_POST['delpost'])) {
            if ($systemUser->rights != 9) {
                echo $tools->displayError(_t('Access forbidden'));
                require('../system/end.php');
                exit;
            }

            $req = $db->query("SELECT `id` FROM `forum` WHERE `type` = 'm' AND `close` = '1' $sort");

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
            $db->exec("DELETE FROM `forum` WHERE `type` = 'm' AND `close` = '1' $sort");

            header('Location: index.php?act=forum&mod=hposts');
        } else {
            $total = $db->query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'm' AND `close` = '1' $sort")->fetchColumn();

            if ($total > $kmess) {
                echo '<div class="topmenu">' . $tools->displayPagination('index.php?act=forum&amp;mod=hposts&amp;', $start, $total, $kmess) . '</div>';
            }

            $req = $db->query("SELECT `forum`.*, `forum`.`id` AS `fid`, `forum`.`user_id` AS `id`, `forum`.`from` AS `name`, `forum`.`soft` AS `browser`, `users`.`rights`, `users`.`lastdate`, `users`.`sex`, `users`.`status`, `users`.`datereg`
            FROM `forum` LEFT JOIN `users` ON `forum`.`user_id` = `users`.`id`
            WHERE `forum`.`type` = 'm' AND `forum`.`close` = '1' $sort ORDER BY `forum`.`id` DESC LIMIT $start, $kmess");

            if ($req->rowCount()) {
                $i = 0;

                while ($res = $req->fetch()) {
                    $res['ip'] = ip2long($res['ip']);
                    $posttime = ' <span class="gray">(' . $tools->displayDate($res['time']) . ')</span>';
                    $page = ceil($db->query("SELECT COUNT(*) FROM `forum` WHERE `refid` = '" . $res['refid'] . "' AND `id` " . ($set_forum['upfp'] ? ">=" : "<=") . " '" . $res['fid'] . "'")->fetchColumn() / $kmess);
                    $text = mb_substr($res['text'], 0, 500);
                    $text = $tools->checkout($text, 1, 0);
                    $text = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $text);
                    $theme = $db->query("SELECT `id`, `text` FROM `forum` WHERE `id` = '" . $res['refid'] . "'")->fetch();
                    $text = '<b>' . $theme['text'] . '</b> <a href="../forum/index.php?id=' . $theme['id'] . '&amp;page=' . $page . '">&gt;&gt;</a><br>' . $text;
                    $subtext = '<span class="gray">' . _t('Filter') . ':</span> ';
                    $subtext .= '<a href="index.php?act=forum&amp;mod=hposts&amp;tsort=' . $theme['id'] . '">' . _t('by topic') . '</a> | ';
                    $subtext .= '<a href="index.php?act=forum&amp;mod=hposts&amp;usort=' . $res['user_id'] . '">' . _t('by author') . '</a>';
                    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                    echo $tools->displayUser($res, [
                        'header' => $posttime,
                        'body'   => $text,
                        'sub'    => $subtext,
                    ]);
                    echo '</div>';
                    ++$i;
                }

                if ($systemUser->rights == 9) {
                    echo '<form action="index.php?act=forum&amp;mod=hposts' . $link . '" method="POST"><div class="rmenu"><input type="submit" name="delpost" value="' . _t('Delete all') . '" /></div></form>';
                }
            } else {
                echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
            }

            echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

            if ($total > $kmess) {
                echo '<div class="topmenu">' . $tools->displayPagination('index.php?act=forum&amp;mod=hposts&amp;', $start, $total, $kmess) . '</div>' .
                    '<p><form action="index.php?act=forum&amp;mod=hposts" method="post">' .
                    '<input type="text" name="page" size="2"/>' .
                    '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
                    '</form></p>';
            }
        }
        break;

    default:
        // Панель управления форумом
        $total_cat = $db->query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'f'")->fetchColumn();
        $total_sub = $db->query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'r'")->fetchColumn();
        $total_thm = $db->query("SELECT COUNT(*) FROM `forum` WHERE `type` = 't'")->fetchColumn();
        $total_thm_del = $db->query("SELECT COUNT(*) FROM `forum` WHERE `type` = 't' AND `close` = '1'")->fetchColumn();
        $total_msg = $db->query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'm'")->fetchColumn();
        $total_msg_del = $db->query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'm' AND `close` = '1'")->fetchColumn();
        $total_files = $db->query("SELECT COUNT(*) FROM `cms_forum_files`")->fetchColumn();
        $total_votes = $db->query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type` = '1'")->fetchColumn();

        echo '<div class="phdr"><a href="index.php"><b>' . _t('Admin Panel') . '</b></a> | ' . _t('Forum Management') . '</div>' .
            '<div class="gmenu"><p><h3>' . $tools->image('rate.gif') . _t('Statistic') . '</h3><ul>' .
            '<li>' . _t('Categories') . ':&#160;' . $total_cat . '</li>' .
            '<li>' . _t('Sections') . ':&#160;' . $total_sub . '</li>' .
            '<li>' . _t('Topics') . ':&#160;' . $total_thm . '&#160;/&#160;<span class="red">' . $total_thm_del . '</span></li>' .
            '<li>' . _t('Messages') . ':&#160;' . $total_msg . '&#160;/&#160;<span class="red">' . $total_msg_del . '</span></li>' .
            '<li>' . _t('Files') . ':&#160;' . $total_files . '</li>' .
            '<li>' . _t('Votes') . ':&#160;' . $total_votes . '</li>' .
            '</ul></p></div>' .
            '<div class="menu"><p><h3><img src="../images/settings.png" width="16" height="16" class="left" />&#160;' . _t('Settings') . '</h3><ul>' .
            '<li><a href="index.php?act=forum&amp;mod=cat"><b>' . _t('Forum structure') . '</b></a></li>' .
            '<li><a href="index.php?act=forum&amp;mod=hposts">' . _t('Hidden posts') . '</a> (' . $total_msg_del . ')</li>' .
            '<li><a href="index.php?act=forum&amp;mod=htopics">' . _t('Hidden topics') . '</a> (' . $total_thm_del . ')</li>' .
            '</ul></p></div>' .
            '<div class="phdr"><a href="../forum/index.php">' . _t('Go to Forum') . '</a></div>';
}

echo '<p><a href="index.php">' . _t('Admin Panel') . '</a></p>';
