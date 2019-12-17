<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Api\NavChainInterface;
use Johncms\System\Config\Config;
use Johncms\System\Users\User;
use Johncms\System\Utility\Tools;
use Johncms\System\View\Render;
use Zend\I18n\Translator\Translator;

defined('_IN_JOHNCMS') || die('Error: restricted access');

$id = isset($_REQUEST['id']) ? abs((int) ($_REQUEST['id'])) : 0;
$act = isset($_GET['act']) ? trim($_GET['act']) : '';
$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';

/**
 * @var Config $config
 * @var PDO $db
 * @var Tools $tools
 * @var Render $view
 * @var User $user
 * @var NavChainInterface $nav_chain
 */

$config = di(Config::class);
$db = di(PDO::class);
$tools = di(Tools::class);
$user = di(User::class);
$view = di(Render::class);
$nav_chain = di(NavChainInterface::class);

// Регистрируем папку с языками модуля
di(Translator::class)->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('downloads', __DIR__ . '/templates/');

// Добавляем раздел в навигационную цепочку
$nav_chain->add(_t('Downloads'), '/downloads/');

$url = '/downloads/';
$urls = [
    'downloads' => $url,
];
const DOWNLOADS = UPLOAD_PATH . 'downloads' . DS;
const DOWNLOADS_SCR = DOWNLOADS . 'screen' . DS;
$files_path = 'upload/downloads/files';

// Настройки
$set_down = [
    'mod'           => 1,
    'theme_screen'  => 1,
    'top'           => 25,
    'video_screen'  => 1,
    'screen_resize' => 1,
];

if ($set_down['video_screen'] && ! extension_loaded('ffmpeg')) {
    $set_down['video_screen'] = 0;
}

// Ограничиваем доступ к Загрузкам
$error = '';

if (! $config['mod_down'] && $user->rights < 7) {
    $error = _t('Downloads are closed');
} elseif ($config['mod_down'] === 1 && ! $user->id) {
    $error = _t('For registered users only');
}

if ($error) {
    echo $view->render(
        'system::pages/result',
        [
            'title'   => _t('Downloads'),
            'type'    => 'alert-danger',
            'message' => $error,
        ]
    );
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
    'webm',
    'avi',
    'mov',
    'aac',
    'm4a',
];

// Переключаем режимы работы
$actions = [
    'bookmark'        => 'bookmark.php',
    'comments'        => 'comments.php',
    'custom_size'     => 'fileControl/custom_size.php',
    'delete_file'     => 'fileControl/delete_file.php',
    'files_upload'    => 'files_upload.php',
    'edit_about'      => 'fileControl/edit_about.php',
    'edit_file'       => 'fileControl/edit_file.php',
    'edit_screen'     => 'fileControl/edit_screen.php',
    'files_more'      => 'fileControl/files_more.php',
    'folder_add'      => 'folder_add.php',
    'folder_delete'   => 'folder_delete.php',
    'folder_edit'     => 'folder_edit.php',
    'import'          => 'files_import.php',
    'jad_file'        => 'fileControl/jad_file.php',
    'load_file'       => 'fileControl/load_file.php',
    'mod_files'       => 'files_moderation.php',
    'mp3tags'         => 'fileControl/mp3tags.php',
    'new_files'       => 'files_new.php',
    'recount'         => 'recount.php',
    'redirect'        => 'redirect.php',
    'review_comments' => 'comments_review.php',
    'scan_dir'        => 'scan_dir.php',
    'search'          => 'search.php',
    'top_files'       => 'files_top.php',
    'top_users'       => 'top_users.php',
    'transfer_file'   => 'fileControl/transfer_file.php',
    'user_files'      => 'files_user.php',
    'view'            => 'view.php',
];

if (isset($actions[$act]) && is_file(__DIR__ . '/includes/' . $actions[$act])) {
    require_once __DIR__ . '/includes/' . $actions[$act];
} else {
    require __DIR__ . '/classes/download.php';

    // Получаем список файлов и папок
    $notice = false;
    $title = _t('Downloads');
    $counters = [];

    if ($id) {
        $cat = $db->query('SELECT * FROM `download__category` WHERE `id` = ' . $id);
        $res_down_cat = $cat->fetch();

        if (! $cat->rowCount() || ! is_dir($res_down_cat['dir'])) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => _t('Wrong data', 'system'),
                    'type'          => 'alert-danger',
                    'message'       => _t('The directory does not exist'),
                    'back_url'      => $url,
                    'back_url_name' => _t('Downloads'),
                ]
            );
            exit;
        }

        $title_pages = htmlspecialchars(mb_substr($res_down_cat['rus_name'], 0, 30));
        $textl = mb_strlen($res_down_cat['rus_name']) > 30 ? $title_pages . '...' : $title_pages;
        Download::navigation(
            [
                'dir'   => $res_down_cat['dir'],
                'refid' => $res_down_cat['refid'],
                'name'  => $res_down_cat['rus_name'],
            ]
        );
        $title = $res_down_cat['rus_name'];
        $total_new = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2'  AND `time` > ${old} AND `dir` LIKE '" . ($res_down_cat['dir']) . "%'")->fetchColumn();

        if ($total_new) {
            $new_url = $url . '?act=new_files&amp;id=' . $id;
        }
    } else {
        $total_new = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '2'  AND `time` > ${old}")->fetchColumn();

        if ($total_new) {
            $new_url = $url . '?act=new_files';
        }
    }
    $urls['new'] = $new_url ?? '';
    $counters['total_new'] = $total_new;

    $urls['mod_files'] = '';
    if ($user->rights === 4 || $user->rights >= 6) {
        $mod_files = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `type` = '3'")->fetchColumn();

        if ($mod_files > 0) {
            $urls['mod_files'] = $url . '?act=mod_files';
        }
    }

    $counters['mod_files'] = $mod_files ?? 0;

    // Выводим список папок и файлов
    $total_cat = $db->query("SELECT COUNT(*) FROM `download__category` WHERE `refid` = '" . $id . "'")->fetchColumn();
    $total_files = $db->query("SELECT COUNT(*) FROM `download__files` WHERE `refid` = '" . $id . "' AND `type` = 2")->fetchColumn();
    $sum_total = $total_files + $total_cat;

    if ($sum_total) {
        if ($total_cat > 0) {
            $req_down = $db->query("SELECT * FROM `download__category` WHERE `refid` = '" . $id . "' ORDER BY `sort`");
            $i = 0;
            $categories = [];
            while ($res_down = $req_down->fetch()) {
                $res_down['rus_name'] = htmlspecialchars($res_down['rus_name']);
                $res_down['desc'] = htmlspecialchars($res_down['desc']);
                $res_down['url'] = $url . '?id=' . $res_down['id'];

                $res_down['up_url'] = $url . '?act=folder_edit&amp;id=' . $res_down['id'] . '&amp;up';
                $res_down['down_url'] = $url . '?act=folder_edit&amp;id=' . $res_down['id'] . '&amp;down';
                $res_down['edit_url'] = $url . '?act=folder_edit&amp;id=' . $res_down['id'];
                $res_down['delete_url'] = $url . '?act=folder_delete&amp;id=' . $res_down['id'];

                $res_down['has_edit'] = $user->rights == 4 || $user->rights >= 6;

                $categories[] = $res_down;
            }
        }

        if ($total_files > 0) {
            if ($total_files > 1) {
                // Сортировка файлов
                if (! isset($_SESSION['sort_down'])) {
                    $_SESSION['sort_down'] = 0;
                }

                if (! isset($_SESSION['sort_down2'])) {
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

                $urls['sort_action'] = $url . '?id=' . $id;
            } else {
                $sql_sort = '';
            }

            // Выводи данные
            $req_down = $db->query("SELECT * FROM `download__files` WHERE `refid` = '" . $id . "' AND `type` < 3 ORDER BY `type` ${sql_sort} LIMIT ${start}, " . $user->config->kmess);
            $files = [];
            while ($res_down = $req_down->fetch()) {
                $files[] = Download::displayFile($res_down);
            }
        }
    }

    echo $view->render(
        'downloads::index',
        [
            'title'       => $title,
            'page_title'  => $title,
            'id'          => $id,
            'urls'        => $urls,
            'counters'    => $counters,
            'pagination'  => $tools->displayPagination($url . '?id=' . $id . '&amp;', $start, $total_files, $user->config->kmess),
            'files'       => $files ?? [],
            'total_files' => $total_files,
            'categories'  => $categories ?? [],
            'total_cat'   => $total_cat,
            'can_upload'  => (isset($res_down_cat['field']) && $res_down_cat['field'] && $user->isValid() && $id),
        ]
    );
}
