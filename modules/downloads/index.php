<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Downloads\Download;
use Johncms\System\Users\User;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\NavChain;
use Johncms\System\i18n\Translator;

defined('_IN_JOHNCMS') || die('Error: restricted access');

$id = isset($_REQUEST['id']) ? abs((int) ($_REQUEST['id'])) : 0;
$act = isset($_GET['act']) ? trim($_GET['act']) : '';
$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';

/**
 * @var PDO $db
 * @var Tools $tools
 * @var Render $view
 * @var User $user
 * @var NavChain $nav_chain
 */

$config = di('config')['johncms'];
$db = di(PDO::class);
$tools = di(Tools::class);
$user = di(User::class);
$view = di(Render::class);
$nav_chain = di(NavChain::class);

// Register the module languages domain and folder
di(Translator::class)->addTranslationDomain('downloads', __DIR__ . '/locale');

$loader = new Aura\Autoload\Loader();
$loader->register();
$loader->addPrefix('Downloads', __DIR__ . '/classes');

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('downloads', __DIR__ . '/templates/');

// Добавляем раздел в навигационную цепочку
$nav_chain->add(__('Downloads'), '/downloads/');

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
    $error = __('Downloads are closed');
} elseif ($config['mod_down'] === 1 && ! $user->id) {
    $error = __('For registered users only');
}

if ($error) {
    echo $view->render(
        'system::pages/result',
        [
            'title'   => __('Downloads'),
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
    'files_upload'    => 'files_upload.php',
    'load_file'       => 'fileControl/load_file.php',
    'new_files'       => 'files_new.php',
    'redirect'        => 'redirect.php',
    'review_comments' => 'comments_review.php',
    'search'          => 'search.php',
    'top_files'       => 'files_top.php',
    'top_users'       => 'top_users.php',
    'user_files'      => 'files_user.php',
    'view'            => 'view.php',
];

if (($user->rights >= 6 || $user->rights === 4)) {
    $admin_actions = [
        'delete_file'   => 'fileControl/delete_file.php',
        'edit_file'     => 'fileControl/edit_file.php',
        'edit_screen'   => 'fileControl/edit_screen.php',
        'files_more'    => 'fileControl/files_more.php',
        'transfer_file' => 'fileControl/transfer_file.php',
        'import'        => 'files_import.php',
        'mod_files'     => 'files_moderation.php',
        'folder_add'    => 'folder_add.php',
        'folder_delete' => 'folder_delete.php',
        'folder_edit'   => 'folder_edit.php',
        'recount'       => 'recount.php',
        'scan_dir'      => 'scan_dir.php',
    ];
    $actions = array_merge($actions, $admin_actions);
}

if (isset($actions[$act]) && is_file(__DIR__ . '/includes/' . $actions[$act])) {
    require_once __DIR__ . '/includes/' . $actions[$act];
} else {
    // Получаем список файлов и папок
    $notice = false;
    $title = __('Downloads');
    $counters = [];

    if ($id) {
        $cat = $db->query('SELECT * FROM `download__category` WHERE `id` = ' . $id);
        $res_down_cat = $cat->fetch();

        if (! $cat->rowCount() || ! is_dir($res_down_cat['dir'])) {
            http_response_code(404);
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => __('Wrong data'),
                    'type'          => 'alert-danger',
                    'message'       => __('The directory does not exist'),
                    'back_url'      => $url,
                    'back_url_name' => __('Downloads'),
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
            'total_new'   => $total_new,
            'categories'  => $categories ?? [],
            'total_cat'   => $total_cat,
            'can_upload'  => (isset($res_down_cat['field']) && $res_down_cat['field'] && $user->isValid() && $id),
        ]
    );
}
