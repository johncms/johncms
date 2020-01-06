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
    1 => _t('Windows applications'),
    2 => _t('Java applications'),
    3 => _t('SIS'),
    4 => _t('txt'),
    5 => _t('Pictures'),
    6 => _t('Archive'),
    7 => _t('Videos'),
    8 => _t('MP3'),
    9 => _t('Other'),
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
    $sql = " AND `cat` = '" . $c . "'";
    $caption = _t('Category Files');
    $input = '<input type="hidden" name="c" value="' . $c . '"/>';
    $type = '';
} elseif ($s) {
    $id = $s;
    $lnk = '&amp;s=' . $s;
    $sql = " AND `subcat` = '" . $s . "'";
    $caption = _t('Section files');
    $input = '<input type="hidden" name="s" value="' . $s . '"/>';
    $type = 'type=topics';
} elseif ($t) {
    $id = $t;
    $lnk = '&amp;t=' . $t;
    $sql = " AND `topic` = '" . $t . "'";
    $caption = _t('Topic Files');
    $input = '<input type="hidden" name="t" value="' . $t . '"/>';
    $type = 'type=topic';
} else {
    $id = false;
    $sql = '';
    $lnk = '';
    $caption = _t('Forum Files');
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
                'message'       => _t('Wrong data'),
                'back_url'      => '/forum/',
                'back_url_name' => _t('Back'),
            ]
        );
        exit;
    }
}

$nav_chain->add($caption);

if ($do || isset($_GET['new'])) {
    // Выводим список файлов нужного раздела
    $total = $db->query('SELECT COUNT(*) FROM `cms_forum_files` WHERE ' . (isset($_GET['new']) ? " `time` > '${new}'" : " `filetype` = '${do}'") . $sql)->fetchColumn();

    if (isset($_GET['new'])) {
        $caption = _t('New Files');
    }
    $files = [];

    if ($total) {
        $req = $db->query(
            'SELECT `cms_forum_files`.*, `forum_messages`.`user_id`, `forum_messages`.`text`, `topicname`.`name` AS `topicname`
            FROM `cms_forum_files`
            LEFT JOIN `forum_messages` ON `cms_forum_files`.`post` = `forum_messages`.`id`
            LEFT JOIN `forum_topic` AS `topicname` ON `cms_forum_files`.`topic` = `topicname`.`id`
            WHERE ' . (isset($_GET['new']) ? " `cms_forum_files`.`time` > '${new}'" : " `filetype` = '${do}'") . ($user->rights >= 7 ? '' : " AND `del` != '1'") . $sql .
            "ORDER BY `time` DESC LIMIT ${start}, " . $user->config->kmess
        );

        while ($res = $req->fetch()) {
            $res_u = $db->query("SELECT `id`, `name`, `sex`, `rights`, `lastdate`, `status`, `datereg`, `ip`, `browser` FROM `users` WHERE `id` = '" . $res['user_id'] . "'")->fetch();
            $text = mb_substr($res['text'], 0, 500);
            $text = $tools->checkout($text, 1, 0);
            $text = preg_replace('#\[c\](.*?)\[/c\]#si', '', $text);
            $res['post_text'] = $text;

            $page = ceil($db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id` = '" . $res['topic'] . "' AND `id` " . ($set_forum['upfp'] ? '>=' : '<=') . " '" . $res['post'] . "'")->fetchColumn() / $user->config->kmess);

            $res['post_time'] = $tools->displayDate($res['time']);
            $res['user_profile_link'] = '';
            if ($user->isValid() && $user->id != $res['user_id'] && ! empty($res_u)) {
                $res['user_profile_link'] = '/profile/?user=' . $res['user_id'];
            }

            $res['user_is_online'] = false;
            $res['user_rights_name'] = '';
            $res['user_name'] = _t('Guest');
            if (! empty($res_u)) {
                $res['user_is_online'] = time() <= $res_u['lastdate'] + 300;
                $res['user_rights_name'] = $user_rights_names[$res_u['rights']] ?? '';
                $res['user_name'] = $res_u['name'];
            }

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
            'back_url_name' => _t('List of sections'),
            'files'         => $files,
            'total'         => $total,
            'new_url'       => '?act=files&amp;new' . $lnk,
        ]
    );
    exit;
}

// Выводим список разделов, в которых есть файлы
$countnew = $db->query("SELECT COUNT(*) FROM `cms_forum_files` WHERE `time` > '${new}'" . ($user->rights >= 7 ? '' : " AND `del` != '1'") . $sql)->fetchColumn();
$link = [];
$total = 0;
$sections = [];
foreach ($types as $key => $type) {
    $count = $db->query("SELECT COUNT(*) FROM `cms_forum_files` WHERE `filetype` = '${key}'" . ($user->rights >= 7 ? '' : " AND `del` != '1'") . $sql)->fetchColumn();
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
