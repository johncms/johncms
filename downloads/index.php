<?php

define('_IN_JOHNCMS', 1);

require('../incfiles/core.php');

/** @var Interop\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Zend\I18n\Translator\Translator $translator */
$translator = $container->get(Zend\I18n\Translator\Translator::class);
$translator->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

$url = $set['homeurl'] . '/downloads/';

//App::autoload()->import('Download', __DIR__ . DS . '_sys' . DS . 'classes' . DS . 'download.php');

$textl = _t('Downloads');
$down_path = '../files/downloads'; //TODO: переделать на константы
$screens_path = '../files/downloads/screen'; //TODO: переделать на константы
$files_path = '../files/downloads/files'; //TODO: переделать на константы

$id = isset($_REQUEST['id']) ? abs(intval($_REQUEST['id'])) : 0;
$act = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : '';

// Настройки
//TODO: Переделать на получение настроек из таблицы модулей
$set_down =
    [
        'mod'           => 1,
        'theme_screen'  => 1,
        'top'           => 25,
        'icon_java'     => 1,
        'video_screen'  => 1,
        'screen_resize' => 1,
    ];

if ($set_down['video_screen'] && !extension_loaded('ffmpeg')) {
    $set_down['video_screen'] = 0;
}

// Ограничиваем доступ к Загрузкам
$error = '';

if (!$set['mod_down'] && $rights < 7) {
    $error = _t('Downloads are closed');
} elseif ($set['mod_down'] == 1 && !$user_id) {
    $error = _t('For registered users only');
}

if ($error) {
    require_once('../incfiles/head.php');
    echo '<div class="rmenu"><p>' . $error . '</p></div>';
    require_once("../incfiles/end.php");
    exit;
}

$old = time() - 259200;

// Список разрешений для выгрузки
$defaultExt = [
    'mp4',
    'rar',
    'zip',
    'pdf',
    'nth',
    'txt',
    'tar',
    'gz',
    'jpg',
    'jpeg',
    'gif',
    'png',
    'bmp',
    '3gp',
    'mp3',
    'mpg',
    'thm',
    'jad',
    'jar',
    'cab',
    'sis',
    'sisx',
    'exe',
    'msi',
    'apk',
    'djvu',
    'fb2',
];

// Переключаем режимы работы
$actions = [
    'add_cat'         => 'category_add.php',
    'bookmark'        => 'bookmark.php',
    'comments'        => 'comments.php',
    'custom_size'     => 'fileControl/custom_size.php',
    'delete_cat'      => 'category_delete.php',
    'delete_file'     => 'fileControl/delete_file.php',
    'down_file'       => 'files_upload.php',
    'edit_about'      => 'fileControl/edit_about.php',
    'edit_cat'        => 'category_edit.php',
    'edit_file'       => 'fileControl/edit_file.php',
    'edit_screen'     => 'fileControl/edit_screen.php',
    'files_more'      => 'fileControl/files_more.php',
    'import'          => 'files_import.php',
    'jad_file'        => 'fileControl/jad_file.php',
    'load_file'       => 'fileControl/load_file.php',
    'mod_files'       => 'files_moderation.php',
    'mp3tags'         => 'fileControl/mp3tags.php',
    'new_files'       => 'files_new.php',
    'open_zip'        => 'fileControl/open_zip.php',
    'recount'         => 'recount.php',
    'redirect'        => 'redirect.php',
    'review_comments' => 'comments_review.php',
    'scan_about'      => 'scan_about.php',
    'scan_dir'        => 'scan_dir.php',
    'search'          => 'search.php',
    'top_files'       => 'files_top.php',
    'top_users'       => 'top_users.php',
    'transfer_file'   => 'fileControl/transfer_file.php',
    'txt_in_jar'      => 'fileControl/txt_in_jar.php',
    'txt_in_zip'      => 'fileControl/txt_in_zip.php',
    'user_files'      => 'files_user.php',
    'view'            => 'view.php',
];

if (isset($actions[$act]) && is_file(__DIR__ . '/includes/' . $actions[$act])) {
    require_once(__DIR__ . '/includes/' . $actions[$act]);
} else {
    /** @var PDO $db */
    $db = $container->get(PDO::class);

    require __DIR__ . '/classes/download.php';
    require '../incfiles/head.php';

    if (!$set['mod_down']) {
        echo '<div class="rmenu">' . _t('Downloads are closed') . '</div>';
    }

    // Получаем список файлов и папок
    $notice = false;

    if ($id) {
        $cat = $db->query("SELECT * FROM `download__category` WHERE `id` = " . $id);
        $res_down_cat = $cat->fetch();

        if (!$cat->rowCount() || !is_dir($res_down_cat['dir'])) {
            echo _t('The directory does not exist') . ' <a href="' . $url . '">' . _t('Downloads') . '</a>';
            exit;
        }

        $title_pages = htmlspecialchars(mb_substr($res_down_cat['rus_name'], 0, 30));
        $textl = mb_strlen($res_down_cat['rus_name']) > 30 ? $title_pages . '...' : $title_pages;
        $navigation = Download::navigation(['dir' => $res_down_cat['dir'], 'refid' => $res_down_cat['refid'], 'name' => $res_down_cat['rus_name']]);
        $total_new = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2'  AND `time` > $old AND `dir` LIKE '" . ($res_down_cat['dir']) . "%'")->fetchColumn();

        if ($total_new) {
            $notice = '<a href="' . $url . '?act=new_files&amp;id=' . $id . '">' . _t('New Files') . '</a> (' . $total_new . ')<br>';
        }
    } else {
        $navigation = '<b>' . _t('Downloads') . '</b></div>' .
            '<div class="topmenu"><a href="' . $url . '?act=search">' . _t('Search') . '</a> | ' .
            '<a href="' . $url . '?act=top_files&amp;id=0">' . _t('Top Files') . '</a> | ' .
            '<a href="' . $url . '?act=top_users">' . _t('Top Users') . '</a>';
        $total_new = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2'  AND `time` > $old")->fetchColumn();

        if ($total_new) {
            $notice = '<a href="' . $url . '?act=new_files&amp;id=' . $id . '">' . _t('New Files') . '</a> (' . $total_new . ')<br>';
        }
    }

    if ($rights == 4 || $rights >= 6) {
        $mod_files = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '3'")->fetchColumn();

        if ($mod_files > 0) {
            $notice .= '<a href="' . $url . '?act=mod_files">' . _t('Files on moderation') . '</a> ' . $mod_files;
        }
    }

    // Уведомления
    if ($notice) {
        echo '<p>' . $notice . '</p>';
    }

    // Навигация
    echo '<div class="phdr">' . $navigation . '</div>';

    // Выводим список папок и файлов
    $total_cat = $db->query("SELECT COUNT(*) FROM `download__category` WHERE `refid` = '" . $id . "'")->fetchColumn();
    $total_files = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `refid` = '" . $id . "' AND `type` = 2")->fetchColumn();
    $sum_total = $total_files + $total_cat;

    if ($sum_total) {
        if ($total_cat > 0) {
            // Выводи папки
            if ($total_files) {
                echo '<div class="phdr"><b>' . _t('List of category') . '</b></div>';
            }

            $req_down = $db->query("SELECT * FROM `download__category` WHERE `refid` = '" . $id . "' ORDER BY `sort` ASC ");
            $i = 0;

            while ($res_down = $req_down->fetch()) {
                echo (($i++ % 2) ? '<div class="list2">' : '<div class="list1">') .
                    //App::image('folder.png', [], true) . '&#160;' .
                    '<a href="' . $url . '?id=' . $res_down['id'] . '">' . htmlspecialchars($res_down['rus_name']) . '</a> (' . $res_down['total'] . ')';

                if ($res_down['field']) {
                    echo '<div><small>' . _t('Allowed extensions') . ': <span class="green"><b>' . $res_down['text'] . '</b></span></small></div>';
                }

                if ($rights == 4 || $rights >= 6 || !empty($res_down['desc'])) {
                    $menu = [
                        '<a href="' . $url . '?act=edit_cat&amp;id=' . $res_down['id'] . '&amp;up">' . _t('Up') . '</a>',
                        '<a href="' . $url . '?act=edit_cat&amp;id=' . $res_down['id'] . '&amp;down">' . _t('Down') . '</a>',
                        '<a href="' . $url . '?act=edit_cat&amp;id=' . $res_down['id'] . '">' . _t('Edit') . '</a>',
                        '<a href="' . $url . '?act=delete_cat&amp;id=' . $res_down['id'] . '">' . _t('Delete') . '</a>',
                    ];
                    echo '<div class="sub">' .
                        (!empty($res_down['desc']) ? '<div class="gray">' . htmlspecialchars($res_down['desc']) . '</div>' : '') .
                        ($rights == 4 || $rights >= 6 ? implode(' | ', $menu) : '') .
                        '</div>';
                }

                echo '</div>';
            }
        }

        if ($total_files > 0) {
            // Выводи файлы
            if ($total_cat) {
                echo '<div class="phdr"><b>' . _t('List of Files') . '</b></div>';
            }

            if ($total_files > 1) {
                // Сортировка файлов
                if (!isset($_SESSION['sort_down'])) {
                    $_SESSION['sort_down'] = 0;
                }

                if (!isset($_SESSION['sort_down2'])) {
                    $_SESSION['sort_down2'] = 0;
                }

                if (isset($_POST['sort_down'])) {
                    $_SESSION['sort_down'] = $_POST['sort_down'] ? 1 : 0;
                }

                if (isset($_POST['sort_down2'])) {
                    $_SESSION['sort_down2'] = $_POST['sort_down2'] ? 1 : 0;
                }

                $sql_sort = isset($_SESSION['sort_down']) && $_SESSION['sort_down'] ? ', `name`' : ', `time`';
                $sql_sort .= isset($_SESSION['sort_down2']) && $_SESSION['sort_down2'] ? ' ASC' : ' DESC';
                echo '<form action="' . $url . '?id=' . $id . '" method="post"><div class="topmenu">' .
                    '<b>' . _t('Sorting') . ': </b>' .
                    '<select name="sort_down" style="font-size:x-small">' .
                    '<option value="0"' . (!$_SESSION['sort_down'] ? ' selected="selected"' : '') . '>' . _t('by time') . '</option>' .
                    '<option value="1"' . ($_SESSION['sort_down'] ? ' selected="selected"' : '') . '>' . _t('by name') . '</option></select> &amp; ' .
                    '<select name="sort_down2" style="font-size:x-small">' .
                    '<option value="0"' . (!$_SESSION['sort_down2'] ? ' selected="selected"' : '') . '>' . _t('descending') . '</option>' .
                    '<option value="1"' . ($_SESSION['sort_down2'] ? ' selected="selected"' : '') . '>' . _t('ascending') . '</option></select>' .
                    '<input type="submit" value="&gt;&gt;" style="font-size:x-small"/></div></form>';
            } else {
                $sql_sort = '';
            }

            // Постраничная навигация
            if ($total_files > $kmess) {
                echo '<div class="topmenu">' . Functions::displayPagination($url . '?id=' . $id . '&amp;', $start, $total_files, $kmess) . '</div>';
            }

            // Выводи данные
            //TODO: Добавить LIMIT
            $req_down = $db->query("SELECT * FROM `download__files` WHERE `refid` = '" . $id . "' AND `type` < 3 ORDER BY `type` ASC $sql_sort ");
            $i = 0;

            while ($res_down = $req_down->fetch()) {
                echo (($i++ % 2) ? '<div class="list2">' : '<div class="list1">') . Download::displayFile($res_down) . '</div>';
            }
        }
    } else {
        echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
    }

    echo '<div class="phdr">';

    if ($total_cat || !$total_files) {
        echo _t('Folders') . ': ' . $total_cat;
    }

    if ($total_cat && $total_files) {
        echo '&nbsp;|&nbsp;';
    }

    if ($total_files) {
        echo _t('Files') . ': ' . $total_files;
    }

    echo '</div>';

    // Постраничная навигация
    if ($total_files > $kmess) {
        echo '<div class="topmenu">' . Functions::displayPagination($url . '?id=' . $id . '&amp;', $start, $total_files, $kmess) . '</div>' .
            '<p><form action="' . $url . '" method="get">' .
            '<input type="hidden" name="id" value="' . $id . '"/>' .
            '<input type="text" name="page" size="2"/><input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
    }

    if ($rights == 4 || $rights >= 6) {
        // Выводим ссылки на модерские функции
        echo '<p><div class="func">';
        echo '<div><a href="?act=add_cat&amp;id=' . $id . '">' . _t('Create Folder') . '</a></div>';

        if ($id) {
            echo '<div><a href="?act=delete_cat&amp;id=' . $id . '">' . _t('Delete Folder') . '</a></div>';
            echo '<div><a href="?act=edit_cat&amp;id=' . $id . '">' . _t('Change Folder') . '</a></div>';
            echo '<div><a href="?act=import&amp;id=' . $id . '">' . _t('Import File') . '</a></div>';
            echo '<div><a href="?act=down_file&amp;id=' . $id . '">' . _t('Upload File') . '</a></div>';
        }

        echo '<div><a href="?act=scan_dir&amp;id=' . $id . '">' . _t('Update Files') . '</a></div>';
        echo '<div><a href="?act=clean&amp;id=' . $id . '">' . _t('Remove missing files') . '</a></div>';
        echo '<div><a href="?act=scan_about&amp;id=' . $id . '">' . _t('Update descriptions') . '</a></div>';
        echo '<div><a href="?act=recount&amp;id=' . $id . '">' . _t('Update counters') . '</a></div>';
        echo '</div></p>';
    } else {
        if (isset($res_down_cat['field']) && $res_down_cat['field'] && $user_id && $id) {
            echo '<p><div class="func"><a href="' . $url . '?act=down_file&amp;id=' . $id . '">' . _t('Upload File') . '</a></div></p>';
        }
    }

    // Нижнее меню навигации
    echo '<p>';

    if ($id) {
        echo '<a href="' . $url . '">' . _t('Downloads') . '</a>';
    } else {
        if ($rights >= 7 || isset($set['mod_down_comm']) && $set['mod_down_comm']) {
            echo '<a href="' . $url . '?act=review_comments">' . _t('Review comments') . '</a><br>';
        }

        echo '<a href="' . $url . '?act=bookmark">' . _t('Favorites') . '</a>';
    }

    echo '</p>';

    require_once('../incfiles/end.php');
}
