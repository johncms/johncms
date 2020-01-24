<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\System\Legacy\Tools;
use Johncms\System\Users\User;
use Johncms\Counters;
use Johncms\System\View\Extension\Assets;
use Johncms\System\View\Render;
use Johncms\NavChain;
use Johncms\System\i18n\Translator;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var Assets $assets
 * @var Counters $counters
 * @var PDO $db
 * @var Tools $tools
 * @var User $user
 * @var Render $view
 * @var NavChain $nav_chain
 */
$assets = di(Assets::class);
$config = di('config')['johncms'];
$counters = di('counters');
$db = di(PDO::class);
$user = di(User::class);
$tools = di(Tools::class);
$view = di(Render::class);
$nav_chain = di(NavChain::class);

// Register the module languages domain and folder
di(Translator::class)->addTranslationDomain('forum', __DIR__ . '/locale');

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('forum', __DIR__ . '/templates/');

// Добавляем раздел в навигационную цепочку
$nav_chain->add(__('Forum'), '/forum/');

$id = isset($_REQUEST['id']) ? abs((int) ($_REQUEST['id'])) : 0;
$act = isset($_GET['act']) ? trim($_GET['act']) : '';
$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';
$do = isset($_REQUEST['do']) ? trim($_REQUEST['do']) : false;

if (isset($_SESSION['ref'])) {
    unset($_SESSION['ref']);
}

// Настройки форума
$set_forum_default = [
    'farea'    => 0,
    'upfp'     => 0,
    'preview'  => 1,
    'postclip' => 1,
    'postcut'  => 2,
];
$set_forum = [];
if ($user->isValid() && ! empty($user->set_forum)) {
    $set_forum = unserialize($user->set_forum, ['allowed_classes' => false]);
}
$set_forum = array_merge($set_forum_default, (array) $set_forum);

// Список расширений файлов, разрешенных к выгрузке

// Файлы архивов
$ext_arch = [
    'zip',
    'rar',
    '7z',
    'tar',
    'gz',
    'apk',
];
// Звуковые файлы
$ext_audio = [
    'mp3',
    'amr',
];
// Файлы документов и тексты
$ext_doc = [
    'txt',
    'pdf',
    'doc',
    'docx',
    'rtf',
    'djvu',
    'xls',
    'xlsx',
];
// Файлы Java
$ext_java = [
    'sis',
    'sisx',
    'apk',
];
// Файлы картинок
$ext_pic = [
    'jpg',
    'jpeg',
    'gif',
    'png',
    'bmp',
];
// Файлы SIS
$ext_sis = [
    'sis',
    'sisx',
];
// Файлы видео
$ext_video = [
    '3gp',
    'avi',
    'flv',
    'mpeg',
    'mp4',
];
// Файлы Windows
$ext_win = [
    'exe',
    'msi',
];
// Другие типы файлов (что не перечислены выше)
$ext_other = ['wmf'];

$user_rights_names = [
    3 => __('Forum moderator'),
    4 => __('Download moderator'),
    5 => __('Library moderator'),
    6 => __('Super moderator'),
    7 => __('Administrator'),
    9 => __('Supervisor'),
];

// Ограничиваем доступ к Форуму
$error = '';

if (! $config['mod_forum'] && $user->rights < 7) {
    $error = __('Forum is closed');
} elseif ($config['mod_forum'] == 1 && ! $user->isValid()) {
    $error = __('For registered users only');
}

if ($error) {
    echo $view->render(
        'system::pages/result',
        [
            'title'   => __('Forum'),
            'type'    => 'alert-danger',
            'message' => $error,
        ]
    );
    exit;
}
$show_type = $_REQUEST['type'] ?? 'section';

// Переключаем режимы работы
$mods = [
    'addfile',
    'addvote',
    'close',
    'deltema',
    'delvote',
    'editpost',
    'editvote',
    'file',
    'files',
    'filter',
    'loadtem',
    'massdel',
    'new',
    'nt',
    'per',
    'show_post',
    'ren',
    'restore',
    'say',
    'search',
    'tema',
    'users',
    'vip',
    'vote',
    'who',
    'curators',
];

if ($act && ($key = array_search($act, $mods)) !== false && file_exists(__DIR__ . '/includes/' . $mods[$key] . '.php')) {
    require __DIR__ . '/includes/' . $mods[$key] . '.php';
} else {
    // Заголовки страниц форума
    if (! empty($id)) {
        // Фиксируем местоположение и получаем заголовок страницы
        switch ($show_type) {
            case 'topics':
            case 'section':
                $res = $db->query('SELECT `name` FROM `forum_sections` WHERE `id`= ' . $id)->fetch();
                break;

            case 'topic':
                $res = $db->query('SELECT `name` FROM `forum_topic` WHERE `id`= ' . $id)->fetch();
                break;

            default:
                $headmod = 'forum';
        }

        $hdr = preg_replace('#\[c\](.*?)\[/c\]#si', '', $res['name']);
        $hdr = strtr(
            $hdr,
            [
                '&laquo;' => '',
                '&raquo;' => '',
                '&quot;'  => '',
                '&amp;'   => '',
                '&lt;'    => '',
                '&gt;'    => '',
                '&#039;'  => '',
            ]
        );
        $hdr = mb_substr($hdr, 0, 30);
        $hdr = $tools->checkout($hdr, 2, 2);
        $textl = empty($hdr) ? __('Forum') : $hdr;
    }

    // Редирект на новые адреса страниц
    if (! empty($id)) {
        $check_section = $db->query("SELECT * FROM `forum_sections` WHERE `id`= '${id}'");
        if (! $check_section->rowCount() && (empty($_REQUEST['type']) || (! empty($_REQUEST['act']) && $_REQUEST['act'] == 'post'))) {
            $check_link = $db->query("SELECT * FROM `forum_redirects` WHERE `old_id`= '${id}'")->fetch();
            if (! empty($check_link)) {
                http_response_code(301);
                header('Location: ' . $check_link['new_link']);
                exit;
            }
        }
    }

    if (! $user->isValid()) {
        if (isset($_GET['newup'])) {
            $_SESSION['uppost'] = 1;
        }

        if (isset($_GET['newdown'])) {
            $_SESSION['uppost'] = 0;
        }
    }

    if ($id) {
        // Определяем тип запроса (каталог, или тема)
        if ($show_type == 'topic') {
            $type = $db->query("SELECT * FROM `forum_topic` WHERE `id`= '${id}'");
        } else {
            $type = $db->query("SELECT * FROM `forum_sections` WHERE `id`= '${id}'");
        }

        if (! $type->rowCount()) {
            // Если темы не существует, показываем ошибку
            echo $view->render(
                'system::pages/result',
                [
                    'title'    => __('Forum'),
                    'type'     => 'alert-danger',
                    'message'  => __('Topic has been deleted or does not exists'),
                    'back_url' => '/forum/',
                ]
            );
            exit;
        }

        $type1 = $type->fetch();

        // Фиксация факта прочтения Топика
        if ($user->isValid() && $show_type == 'topic') {
            $db->query(
                "INSERT INTO `cms_forum_rdm` (topic_id,  user_id, `time`)
                VALUES ('${id}', '" . $user->id . "', '" . time() . "')
                ON DUPLICATE KEY UPDATE `time` = VALUES(`time`)"
            );
        }

        // Nav chain
        $res = true;
        $allow = 0;
        $parent = $show_type == 'topic' ? $type1['section_id'] : $type1['parent'];
        $tree = [];
        while (! empty($parent) && $res != false) {
            $res = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '${parent}' LIMIT 1")->fetch();
            $tree[] = $res;
            if ($res['section_type'] == 1 && ! empty($res['access'])) {
                $allow = (int) $res['access'];
            }
            $parent = $res['parent'];
        }
        krsort($tree);
        foreach ($tree as $item) {
            $nav_chain->add($item['name'], '/forum/?' . ($item['section_type'] == 1 ? 'type=topics&amp;' : '') . 'id=' . $item['id']);
        }

        $nav_chain->add($type1['name']);

        // Счетчик файлов и ссылка на них
        $sql = ($user->rights == 9) ? '' : " AND `del` != '1'";

        if ($show_type == 'topic') {
            $count = $db->query("SELECT COUNT(*) FROM `cms_forum_files` WHERE `topic` = '${id}'" . $sql)->fetchColumn();
        } elseif ($type1['section_type'] == 0) {
            $count = $db->query('SELECT COUNT(*) FROM `cms_forum_files` WHERE `cat` = ' . $type1['id'] . $sql)->fetchColumn();
        } elseif ($type1['section_type'] == 1) {
            $count = $db->query("SELECT COUNT(*) FROM `cms_forum_files` WHERE `subcat` = '${id}'" . $sql)->fetchColumn();
        }

        switch ($show_type) {
            case 'section':
                // List of forum sections
                $req = $db->query("SELECT * FROM `forum_sections` WHERE `parent`='${id}' ORDER BY `sort`");
                $total = $req->rowCount();
                $sections = [];
                if ($total) {
                    while ($res = $req->fetch()) {
                        if ($res['section_type'] == 1) {
                            $children_count = $db->query("SELECT COUNT(*) FROM `forum_topic` WHERE `section_id` = '" . $res['id'] . "'" . ($user->rights >= 7 ? '' : " AND (`deleted` != '1' OR deleted IS NULL)"))->fetchColumn();
                        } else {
                            $children_count = $db->query("SELECT COUNT(*) FROM `forum_sections` WHERE `parent` = '" . $res['id'] . "'")->fetchColumn();
                        }

                        $res['children_count'] = $children_count;
                        $res['url'] = '?' . ($res['section_type'] == 1 ? 'type=topics&amp;' : '') . 'id=' . $res['id'];
                        $sections[] = $res;
                    }
                    unset($_SESSION['fsort_id'], $_SESSION['fsort_users']);
                }

                $online = $db->query(
                    'SELECT (SELECT COUNT(*) FROM `users` WHERE `lastdate` > ' . (time() - 300) . " AND `place` LIKE '/forum%') AS online_u,
       (SELECT COUNT(*) FROM `cms_sessions` WHERE `lastdate` > " . (time() - 300) . " AND `place` LIKE '/forum%') AS online_g"
                )->fetch();

                echo $view->render(
                    'forum::section',
                    [
                        'title'        => $type1['name'],
                        'page_title'   => $type1['name'],
                        'id'           => $type1['id'],
                        'sections'     => $sections,
                        'online'       => $online,
                        'total'        => $total,
                        'files_count'  => $tools->formatNumber($count),
                        'unread_count' => $tools->formatNumber($counters->forumUnreadCount()),
                    ]
                );
                break;

            case 'topics':
                // List of forum topics
                $total = $db->query("SELECT COUNT(*) FROM `forum_topic` WHERE `section_id` = '${id}'" . ($user->rights >= 7 ? '' : " AND (`deleted` != '1' OR deleted IS NULL)"))->fetchColumn();
                if ($total) {
                    $req = $db->query(
                        'SELECT tpc.*, (
SELECT COUNT(*) FROM `cms_forum_rdm` WHERE `time` >= tpc.last_post_date AND `topic_id` = tpc.id AND `user_id` = ' . $user->id . ") as `np`
FROM `forum_topic` tpc WHERE `section_id` = '${id}'" . ($user->rights >= 7 ? '' : " AND (`deleted` <> '1' OR deleted IS NULL)") . "
ORDER BY `pinned` DESC, `last_post_date` DESC LIMIT ${start}, " . $user->config->kmess
                    );

                    $topics = [];
                    while ($res = $req->fetch()) {
                        if ($user->rights >= 7) {
                            $cpg = ceil($res['mod_post_count'] / $user->config->kmess);
                            $res['show_posts_count'] = $tools->formatNumber($res['mod_post_count']);
                            $res['show_last_author'] = $res['mod_last_post_author_name'];
                            $res['show_last_post_date'] = $tools->displayDate($res['mod_last_post_date']);
                        } else {
                            $cpg = ceil($res['post_count'] / $user->config->kmess);
                            $res['show_posts_count'] = $tools->formatNumber($res['post_count']);
                            $res['show_last_author'] = $res['last_post_author_name'];
                            $res['show_last_post_date'] = $tools->displayDate($res['last_post_date']);
                        }

                        $res['has_icons'] = ($res['pinned'] || $res['has_poll'] || $res['closed'] || $res['deleted']);

                        $res['url'] = '/forum/?type=topic&amp;id=' . $res['id'];

                        // Url to last page
                        $res['last_page_url'] = '';
                        if ($cpg > 1) {
                            $res['last_page_url'] = '/forum/?type=topic&amp;id=' . $res['id'] . '&amp;page=' . $cpg;
                        }

                        $topics[] = $res;
                    }
                    unset($_SESSION['fsort_id'], $_SESSION['fsort_users']);
                }

                $online = $db->query(
                    'SELECT (SELECT COUNT(*) FROM `users` WHERE `lastdate` > ' . (time() - 300) . " AND `place` LIKE '/forum%') AS online_u,
       (SELECT COUNT(*) FROM `cms_sessions` WHERE `lastdate` > " . (time() - 300) . " AND `place` LIKE '/forum%') AS online_g"
                )->fetch();

                // Check access to create topic
                $create_access = false;
                if (($user->isValid() && ! isset($user->ban['1']) && ! isset($user->ban['11']) && $config['mod_forum'] != 4) || $user->rights) {
                    $create_access = true;
                }

                echo $view->render(
                    'forum::topics',
                    [
                        'pagination'    => $tools->displayPagination('?type=topics&id=' . $id . '&amp;', $start, $total, $user->config->kmess),
                        'id'            => $id,
                        'create_access' => $create_access,
                        'title'         => $type1['name'],
                        'page_title'    => $type1['name'],
                        'topics'        => $topics ?? [],
                        'online'        => $online,
                        'total'         => $total,
                        'files_count'   => $tools->formatNumber($count),
                        'unread_count'  => $tools->formatNumber($counters->forumUnreadCount()),
                    ]
                );
                break;

            case 'topic':
                // List messages
                if ($user->isValid()) {
                    $online = $db->query(
                        'SELECT (
SELECT COUNT(*) FROM `users` WHERE `lastdate` > ' . (time() - 300) . " AND `place` LIKE '/forum?type=topic&id=${id}%') AS online_u, (
SELECT COUNT(*) FROM `cms_sessions` WHERE `lastdate` > " . (time() - 300) . " AND `place` LIKE '/forum?type=topic&id=${id}%') AS online_g"
                    )->fetch();
                }

                $filter = isset($_SESSION['fsort_id']) && $_SESSION['fsort_id'] == $id ? 1 : 0;
                $sql = '';

                if ($filter && ! empty($_SESSION['fsort_users'])) {
                    // Подготавливаем запрос на фильтрацию юзеров
                    $sw = 0;
                    $sql = ' AND (';
                    $fsort_users = unserialize($_SESSION['fsort_users'], ['allowed_classes' => false]);

                    foreach ($fsort_users as $val) {
                        if ($sw) {
                            $sql .= ' OR ';
                        }

                        $sortid = (int) $val;
                        $sql .= "`forum_messages`.`user_id` = '${sortid}'";
                        $sw = 1;
                    }
                    $sql .= ')';
                }

                // Если тема помечена для удаления, разрешаем доступ только администрации
                if ($user->rights < 6 && $type1['deleted'] == 1) {
                    echo $view->render(
                        'system::pages/result',
                        [
                            'title'         => __('Topic deleted'),
                            'type'          => 'alert-danger',
                            'message'       => __('Topic deleted'),
                            'back_url'      => '?type=topics&amp;id=' . $type1['section_id'],
                            'back_url_name' => __('Go to Section'),
                        ]
                    );
                    exit;
                }

                $view_count = (int) $type1['view_count'];
                // Фиксируем количество просмотров топика
                if (! empty($type1['id']) && (empty($_SESSION['viewed_topics']) || ! in_array($type1['id'], $_SESSION['viewed_topics']))) {
                    $view_count = (int) $type1['view_count'] + 1;
                    $db->query('UPDATE forum_topic SET view_count = ' . $view_count . ' WHERE id = ' . $type1['id']);
                    $_SESSION['viewed_topics'][] = $type1['id'];
                }

                // Счетчик постов темы
                $total = $db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id`='${id}'${sql}" . ($user->rights >= 7 ? '' : " AND (`deleted` != '1' OR `deleted` IS NULL)"))->fetchColumn();

                if ($start >= $total) {
                    // Исправляем запрос на несуществующую страницу
                    $start = max(0, $total - (($total % $user->config->kmess) == 0 ? $user->config->kmess : ($total % $user->config->kmess)));
                }

                $poll_data = [];
                if ($type1['has_poll']) {
                    $clip_forum = isset($_GET['clip']) ? '&amp;clip' : '';
                    $topic_vote = $db->query(
                        "SELECT `fvt`.`name`, `fvt`.`time`, `fvt`.`count`, (
SELECT COUNT(*) FROM `cms_forum_vote_users` WHERE `user`='" . $user->id . "' AND `topic`='" . $id . "') as vote_user
FROM `cms_forum_vote` `fvt` WHERE `fvt`.`type`='1' AND `fvt`.`topic`='" . $id . "' LIMIT 1"
                    )->fetch();
                    $topic_vote['name'] = $tools->checkout($topic_vote['name'], 0, 0);
                    $poll_data['poll'] = $topic_vote;
                    $poll_data['show_form'] = (! $type1['closed'] && ! isset($_GET['vote_result']) && $user->isValid() && $topic_vote['vote_user'] == 0);
                    $poll_data['results'] = [];

                    $vote_result = $db->query("SELECT `id`, `name`, `count` FROM `cms_forum_vote` WHERE `type`='2' AND `topic`='" . $id . "' ORDER BY `id` ASC");
                    while ($vote = $vote_result->fetch()) {
                        $vote['name'] = $tools->checkout($vote['name'], 0, 1);
                        $count_vote = $topic_vote['count'] ? round(100 / $topic_vote['count'] * $vote['count']) : 0;

                        $color = null;
                        if ($count_vote > 0 && $count_vote <= 25) {
                            $color = 'bg-success';
                        } elseif ($count_vote > 25 && $count_vote <= 50) {
                            $color = 'bg-info';
                        } elseif ($count_vote > 50 && $count_vote <= 75) {
                            $color = 'bg-warning';
                        } elseif ($count_vote > 75 && $count_vote <= 100) {
                            $color = 'bg-danger';
                        }

                        $vote['color_class'] = $color;
                        $vote['vote_percent'] = $count_vote;
                        $poll_data['results'][] = $vote;
                    }

                    $poll_data['clip'] = $clip_forum;
                }

                // Получаем данные о кураторах темы
                $curators = ! empty($type1['curators']) ? unserialize($type1['curators'], ['allowed_classes' => false]) : [];
                $curator = false;

                if ($user->rights < 6 && $user->rights != 3 && $user->isValid()) {
                    if (array_key_exists($user->id, $curators)) {
                        $curator = true;
                    }
                }

                // Fixed first post
                $first_post = [];
                if (($set_forum['postclip'] == 2 && ($set_forum['upfp'] ? $start < (ceil($total - $user->config->kmess)) : $start > 0)) || isset($_GET['clip'])) {
                    $message = $db->query(
                        "SELECT `forum_messages`.*, `users`.`sex`, `users`.`rights`, `users`.`lastdate`, `users`.`status`, `users`.`datereg`
                    FROM `forum_messages` LEFT JOIN `users` ON `forum_messages`.`user_id` = `users`.`id`
                    WHERE `forum_messages`.`topic_id` = '${id}'" . ($user->rights >= 7 ? '' : " AND (`forum_messages`.`deleted` != '1' OR `forum_messages`.`deleted` IS NULL)") . '
                    ORDER BY `forum_messages`.`id` LIMIT 1'
                    )->fetch();

                    $message['user_profile_link'] = '';
                    if ($user->isValid() && $user->id != $message['user_id']) {
                        $message['user_profile_link'] = '/profile/?user=' . $message['user_id'];
                    }
                    $message['user_rights_name'] = $user_rights_names[$message['rights']] ?? '';
                    $message['user_is_online'] = time() <= $message['lastdate'] + 300;
                    $message['post_time'] = $tools->displayDate($message['date']);

                    $message['post_text'] = $tools->checkout($message['text'], 1, 1);
                    $message['post_text'] = $tools->smilies($message['post_text'], $message['rights'] ? 1 : 0);

                    $message['post_preview'] = '';
                    if (mb_strlen($message['text']) > 500) {
                        $message['post_preview'] = $tools->checkout(mb_substr($message['text'], 0, 500), 0, 2);
                        $message['post_preview'] = $message['post_preview'] . '...';
                    }

                    $first_post = $message;
                }

                // Задаем правила сортировки (новые внизу / вверху)
                if ($user->isValid()) {
                    $order = $set_forum['upfp'] ? 'DESC' : 'ASC';
                } else {
                    $order = ((empty($_SESSION['uppost'])) || ($_SESSION['uppost'] == 0)) ? 'ASC' : 'DESC';
                }

                // Messages
                $req = $db->query(
                    "
                  SELECT `forum_messages`.*, `users`.`sex`, `users`.`rights`, `users`.`lastdate`, `users`.`status`, `users`.`datereg`, (
                  SELECT COUNT(*) FROM `cms_forum_files` WHERE `post` = forum_messages.id) as file
                  FROM `forum_messages` LEFT JOIN `users` ON `forum_messages`.`user_id` = `users`.`id`
                  WHERE `forum_messages`.`topic_id` = '${id}'"
                    . ($user->rights >= 7 ? '' : " AND (`forum_messages`.`deleted` != '1' OR `forum_messages`.`deleted` IS NULL)") . "${sql}
                  ORDER BY `forum_messages`.`id` ${order} LIMIT ${start}, " . $user->config->kmess
                );

                $i = 1;
                $messages = [];
                while ($res = $req->fetch()) {
                    $res['user_profile_link'] = '';
                    if ($user->isValid() && $user->id != $res['user_id']) {
                        $res['user_profile_link'] = '/profile/?user=' . $res['user_id'];
                    }

                    $res['user_rights_name'] = $user_rights_names[$res['rights']] ?? '';

                    $res['user_is_online'] = time() <= $res['lastdate'] + 300;
                    $res['post_url'] = '/forum/?act=show_post&amp;id=' . $res['id'];

                    $res['reply_url'] = '';
                    $res['quote_url'] = '';
                    if ($user->isValid() && $user->id != $res['user_id']) {
                        $res['reply_url'] = '/forum/?act=say&amp;type=reply&amp;id=' . $res['id'] . '&amp;start=' . $start;
                        $res['quote_url'] = '/forum/?act=say&amp;type=reply&amp;id=' . $res['id'] . '&amp;start=' . $start . '&amp;cyt';
                    }

                    $res['post_time'] = $tools->displayDate($res['date']);

                    $text = $res['text'];
                    $text = $tools->checkout($text, 1, 1);
                    $text = $tools->smilies($text, $res['rights'] ? 1 : 0);
                    $res['post_text'] = $text;

                    $res['edit_time'] = $res['edit_count'] ? $tools->displayDate($res['edit_time']) : '';

                    // Access to edit post
                    $res['has_edit'] = false;
                    if (
                        (($user->rights == 3 || $user->rights >= 6 || $curator) && $user->rights >= $res['rights'])
                        || ($res['user_id'] == $user->id && ! $set_forum['upfp'] && ($start + $i) == $total && $res['date'] > time() - 300)
                        || ($res['user_id'] == $user->id && $set_forum['upfp'] && $start == 0 && $i == 1 && $res['date'] > time() - 300)
                        || ($i == 1 && $allow == 2 && $res['user_id'] == $user->id)
                    ) {
                        $res['has_edit'] = true;
                    }

                    // Attachments
                    $res['files'] = [];
                    if ($res['file']) {
                        $freq = $db->query("SELECT * FROM `cms_forum_files` WHERE `post` = '" . $res['id'] . "'");
                        while ($fres = $freq->fetch()) {
                            $file_params = [];
                            $file_params['file_size'] = round(@filesize(UPLOAD_PATH . 'forum/attach/' . $fres['filename']) / 1024, 2);

                            $att_ext = strtolower(pathinfo(UPLOAD_PATH . 'forum/attach/' . $fres['filename'], PATHINFO_EXTENSION));
                            $pic_ext = [
                                'gif',
                                'jpg',
                                'jpeg',
                                'png',
                            ];

                            $file_params['file_preview'] = '';
                            $file_params['file_url'] = '/forum/?act=file&amp;id=' . $fres['id'];
                            $file_params['delete_url'] = '/forum/?act=editpost&amp;do=delfile&amp;fid=' . $fres['id'] . '&amp;id=' . $res['id'];
                            if (in_array($att_ext, $pic_ext)) {
                                $file_params['file_preview'] = '/assets/modules/forum/thumbinal.php?file=' . (urlencode($fres['filename']));
                            }

                            $res['files'][] = array_merge($fres, $file_params);
                        }
                    }

                    $res['search_ip_url'] = '/admin/search_ip/?ip=' . long2ip($res['ip']);
                    $res['ip'] = long2ip($res['ip']);
                    $res['search_ip_via_proxy_url'] = '/admin/search_ip/?ip=' . long2ip($res['ip_via_proxy']);
                    $res['ip_via_proxy'] = ! empty($res['ip_via_proxy']) ? long2ip($res['ip_via_proxy']) : 0;

                    if ($res['has_edit']) {
                        $res['edit_url'] = '/forum/?act=editpost&amp;id=' . $res['id'];
                        $res['delete_url'] = '/forum/?act=editpost&amp;do=del&amp;id=' . $res['id'];
                        $res['restore_url'] = '';
                        if ($user->rights >= 7 && $res['deleted'] == 1) {
                            $res['restore_url'] = '?act=editpost&amp;do=restore&amp;id=' . $res['id'];
                        }
                    }

                    $messages[] = $res;
                    $i++;
                }

                // Нижнее поле "Написать"
                $write_access = false;
                if (($user->isValid() && ! $type1['closed'] && $config['mod_forum'] != 3 && $allow != 4) || ($user->rights >= 7)) {
                    $write_access = true;
                    if ($set_forum['farea']) {
                        $token = mt_rand(1000, 100000);
                        $_SESSION['token'] = $token;
                    }
                }

                // Список кураторов
                $curators_array = [];
                if ($curators) {
                    foreach ($curators as $key => $value) {
                        $curators_array[] = '<a href="/profile/?user=' . $key . '">' . $value . '</a>';
                    }
                }

                echo $view->render(
                    'forum::topic',
                    [
                        'first_post'       => $first_post,
                        'topic'            => $type1,
                        'topic_vote'       => $topic_vote ?? null,
                        'curators_array'   => $curators_array,
                        'view_count'       => $view_count,
                        'pagination'       => $tools->displayPagination('/forum/?type=topic&id=' . $id . '&amp;', $start, $total, $user->config->kmess),
                        'start'            => $start,
                        'id'               => $id,
                        'token'            => $token ?? null,
                        'bbcode'           => di(Johncms\System\Legacy\Bbcode::class)->buttons('new_message', 'msg'),
                        'settings_forum'   => $set_forum,
                        'write_access'     => $write_access,
                        'title'            => $type1['name'],
                        'page_title'       => $type1['name'],
                        'messages'         => $messages ?? [],
                        'online'           => $online ?? [],
                        'total'            => $total,
                        'files_count'      => $tools->formatNumber($count),
                        'unread_count'     => $tools->formatNumber($counters->forumUnreadCount()),
                        'filter_by_author' => $filter,
                        'poll_data'        => $poll_data,
                    ]
                );
                break;

            default:
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'         => __('Wrong data'),
                        'type'          => 'alert-danger',
                        'message'       => __('Wrong data'),
                        'back_url'      => '/forum/',
                        'back_url_name' => __('Go to Forum'),
                    ]
                );
                break;
        }
    } else {
        // Forum categories

        $count = $db->query('SELECT COUNT(*) FROM `cms_forum_files`' . ($user->rights >= 7 ? '' : " WHERE `del` != '1'"))->fetchColumn();
        $req = $db->query(
            'SELECT sct.`id`, sct.`name`, sct.`description`, (
SELECT COUNT(*) FROM `forum_sections` WHERE `parent`=sct.id) as cnt
FROM `forum_sections` sct WHERE sct.parent IS NULL OR sct.parent = 0 ORDER BY sct.`sort`'
        );

        $sections = [];
        while ($res = $req->fetch()) {
            $subsections_array = [];
            $subsections = $db->query('SELECT * FROM `forum_sections` WHERE parent = ' . $res['id'] . ' ORDER BY `sort`');
            while ($arr = $subsections->fetch()) {
                $type = ! empty($arr['section_type']) ? 'topics' : 'sections';
                $arr['url'] = '/forum/?type=' . $type . '&id=' . $arr['id'];
                $subsections_array[] = $arr;
            }

            $res['subsections'] = $subsections_array;
            $res['url'] = '/forum/?id=' . $res['id'];
            $sections[] = $res;
        }

        $online = $db->query(
            'SELECT (SELECT COUNT(*) FROM `users` WHERE `lastdate` > ' . (time() - 300) . " AND `place` LIKE '/forum%') AS online_u,
       (SELECT COUNT(*) FROM `cms_sessions` WHERE `lastdate` > " . (time() - 300) . " AND `place` LIKE '/forum%') AS online_g"
        )->fetch();
        unset($_SESSION['fsort_id'], $_SESSION['fsort_users']);

        echo $view->render(
            'forum::index',
            [
                'title'        => __('Forum'),
                'page_title'   => __('Forum'),
                'sections'     => $sections,
                'online'       => $online,
                'files_count'  => $tools->formatNumber($count),
                'unread_count' => $tools->formatNumber($counters->forumUnreadCount()),
            ]
        );
    }
}
