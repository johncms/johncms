<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 */

$types = [
    1 => __('Windows applications'),
    2 => __('Java applications'),
    3 => __('SIS'),
    4 => __('txt'),
    5 => __('Pictures'),
    6 => __('Archive'),
    7 => __('Videos'),
    8 => __('MP3'),
    9 => __('Other'),
];
$new = time() - 86400; // Сколько времени файлы считать новыми?

// Получаем ID раздела и подготавливаем запрос
$c = isset($_GET['c']) ? abs((int) ($_GET['c'])) : false; // ID раздела
$s = isset($_GET['s']) ? abs((int) ($_GET['s'])) : false; // ID подраздела
$t = isset($_GET['t']) ? abs((int) ($_GET['t'])) : false; // ID топика
$do = isset($_GET['do']) && (int) ($_GET['do']) > 0 && (int) ($_GET['do']) < 10 ? (int) ($_GET['do']) : 0;

if ($c) {
    $id = $c;
    $lnk = '&amp;c=' . $c;
    $sql = " AND `files`.`cat` = '" . $c . "'";
    $caption = __('Category Files');
    $input = '<input type="hidden" name="c" value="' . $c . '"/>';
    $type = '';
} elseif ($s) {
    $id = $s;
    $lnk = '&amp;s=' . $s;
    $sql = " AND `files`.`subcat` = '" . $s . "'";
    $caption = __('Section files');
    $input = '<input type="hidden" name="s" value="' . $s . '"/>';
    $type = 'type=topics';
} elseif ($t) {
    $id = $t;
    $lnk = '&amp;t=' . $t;
    $sql = " AND `files`.`topic` = '" . $t . "'";
    $caption = __('Topic Files');
    $input = '<input type="hidden" name="t" value="' . $t . '"/>';
    $type = 'type=topic';
} else {
    $id = false;
    $sql = '';
    $lnk = '';
    $caption = __('Forum Files');
    $input = '';
    $type = '';
}

if ($c || $s || $t) {
    // Получаем имя нужной категории форума
    if (! empty($t)) {
        $req = $db->query("SELECT * FROM `forum_topic` WHERE `id` = '${id}'");
    } elseif (! empty($s)) {
        $req = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '${id}'");
    } elseif (! empty($c)) {
        $req = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '${id}'");
    }

    if ($req->rowCount()) {
        $res = $req->fetch();
        $nav_chain->add($res['name'], '/forum/?' . $type . '&amp;id=' . $res['id']);
    } else {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => $caption,
                'page_title'    => $caption,
                'type'          => 'alert-danger',
                'message'       => __('Wrong data'),
                'back_url'      => '/forum/',
                'back_url_name' => __('Back'),
            ]
        );
        exit;
    }
}

$nav_chain->add($caption);

if ($do || isset($_GET['new'])) {
    // Выводим список файлов нужного раздела
    $total = $db->query('SELECT COUNT(*) FROM `cms_forum_files` `files` WHERE ' . (isset($_GET['new']) ? " `time` > '${new}'" : " `filetype` = '${do}'") . $sql)->fetchColumn();

    if (isset($_GET['new'])) {
        $caption = __('New Files');
    }
    $files = [];

    if ($total) {
        $req = $db->query('SELECT `files`.*,
    `mess`.`user_id`,
    `mess`.`text`,
    `topicname`.`name` AS `topicname`,
    u.`name`,
    u.`sex`,
    u.`rights`,
    u.`lastdate`,
    u.`status`,
    u.`datereg`,
    u.`ip`,
    u.`browser`, (
    SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id` = `files`.`topic` AND `id` ' . ($set_forum['upfp'] ? '>=' : '<=') . ' `files`.`post` ) AS `page`
FROM `cms_forum_files` files
JOIN `forum_messages` mess ON `files`.`post` = `mess`.`id`
JOIN `forum_topic` AS `topicname` ON `files`.`topic` = `topicname`.`id`
JOIN `users` u ON u.`id` = `mess`.`user_id`
WHERE ' . (isset($_GET['new']) ? " `files`.`time` > '${new}'" : " `filetype` = '${do}'") . ($user->rights >= 7 ? '' : " AND `del` <> '1'") . $sql . "
ORDER BY `time` DESC LIMIT ${start}, " . $user->config->kmess);

        while ($res = $req->fetch()) {
            $text = mb_substr($res['text'], 0, 500);
            $text = $tools->checkout($text, 1, 0);
            $text = preg_replace('/\[\/?(\w+).*?\]/is', '', $text);
            $res['post_text'] = $text;

            $page = ceil($res['page'] / $user->config->kmess);

            $res['post_time'] = $tools->displayDate($res['time']);
            $res['user_profile_link'] = '';
            if ($user->isValid() && $user->id != $res['user_id']) {
                $res['user_profile_link'] = '/profile/?user=' . $res['user_id'];
            }

            $res['user_rights_name'] = $user_rights_names[$res['rights']] ?? '';
            $res['user_name'] = $res['name'];

            $res['post_url'] = '/forum/?act=show_post&amp;id=' . $res['post'];
            $res['topic_url'] = '/forum/?type=topic&id=' . $res['topic'] . '&amp;page=' . $page;

            $fls = @filesize(UPLOAD_PATH . 'forum/attach/' . $res['filename']);
            $res['file_size'] = round($fls / 1024, 0);
            $att_ext = strtolower(pathinfo(UPLOAD_PATH . 'forum/attach/' . $res['filename'], PATHINFO_EXTENSION));
            $pic_ext = [
                'gif',
                'jpg',
                'jpeg',
                'png',
            ];

            $res['file_preview'] = '';
            $res['file_url'] = '/forum/?act=file&amp;id=' . $res['id'];
            if (in_array($att_ext, $pic_ext)) {
                $res['file_preview'] = '/assets/modules/forum/thumbinal.php?file=' . (urlencode($res['filename']));
            }

            $files[] = $res;
        }
    }

    echo $view->render(
        'forum::files_list',
        [
            'title'         => $caption,
            'page_title'    => $caption,
            'pagination'    => $tools->displayPagination('?act=files&amp;' . (isset($_GET['new']) ? 'new' : 'do=' . $do) . $lnk . '&amp;', $start, $total, $user->config->kmess),
            'back_url'      => '/forum/?act=files' . $lnk,
            'back_url_name' => __('List of sections'),
            'files'         => $files,
            'total'         => $total,
            'new_url'       => '?act=files&amp;new' . $lnk,
        ]
    );
    exit;
}

// Выводим список разделов, в которых есть файлы
$countnew = $db->query("SELECT COUNT(*) FROM `cms_forum_files` `files` WHERE `time` > '${new}'" . ($user->rights >= 7 ? '' : " AND `del` != '1'") . $sql)->fetchColumn();
$link = [];
$total = 0;
$sections = [];
foreach ($types as $key => $type) {
    $count = $db->query("SELECT COUNT(*) FROM `cms_forum_files` `files` WHERE `filetype` = '${key}'" . ($user->rights >= 7 ? '' : " AND `del` != '1'") . $sql)->fetchColumn();
    if ($count > 0) {
        $sections[] = [
            'url'   => '/forum/?act=files&amp;do=' . $key . $lnk,
            'name'  => $type,
            'count' => $count,
        ];
    }
    $total = $total + $count;
}

echo $view->render(
    'forum::files_sections',
    [
        'title'      => $caption,
        'page_title' => $caption,
        'back_url'   => '?type=topic&id=' . $id,
        'sections'   => $sections,
        'total'      => $total,
        'new_url'    => '?act=files&amp;new' . $lnk,
        'new_count'  => $countnew,
    ]
);
