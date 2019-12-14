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
 * @var Johncms\Api\ToolsInterface $tools
 * @var Johncms\Api\UserInterface $user
 */

if (empty($_GET['id'])) {
    http_response_code(404);
    echo $view->render(
        'system::pages/result',
        [
            'title'         => _t('Show post'),
            'type'          => 'alert-danger',
            'message'       => _t('Wrong data'),
            'back_url'      => '/forum/',
            'back_url_name' => _t('Forum'),
        ]
    );
    exit;
}

// Запрос сообщения
$res = $db->query(
    "SELECT `forum_messages`.*, `users`.`sex`, `users`.`rights`, `users`.`lastdate`, `users`.`status`, `users`.`datereg`
FROM `forum_messages` LEFT JOIN `users` ON `forum_messages`.`user_id` = `users`.`id`
WHERE `forum_messages`.`id` = '${id}'" . ($user->rights >= 7 ? '' : " AND (`forum_messages`.`deleted` != '1' OR `forum_messages`.`deleted` IS NULL)") . ' LIMIT 1'
)->fetch();

if (! $res) {
    http_response_code(404);
    echo $view->render(
        'system::pages/result',
        [
            'title'         => _t('Show post'),
            'type'          => 'alert-danger',
            'message'       => _t('Wrong data'),
            'back_url'      => '/forum/',
            'back_url_name' => _t('Forum'),
        ]
    );
    exit;    
}
// Запрос темы
$them = $db->query("SELECT * FROM `forum_topic` WHERE `id` = '" . $res['topic_id'] . "'")->fetch();

$post = [];

$res['user_avatar'] = '';
$avatar = 'users/avatar/' . $res['user_id'] . '.png';
if (file_exists(UPLOAD_PATH . $avatar)) {
    $res['user_avatar'] = UPLOAD_PUBLIC_PATH . $avatar;
}

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

// Если есть прикрепленный файл, выводим его описание
$freq = $db->query("SELECT * FROM `cms_forum_files` WHERE `post` = '" . $res['id'] . "'");
$res['files'] = [];
if ($freq->rowCount()) {
    $fres = $freq->fetch();
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

$post = $res;

// Вычисляем, на какой странице сообщение?
$page = ceil($db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id` = '" . $res['topic_id'] . "' AND `id` " . ($set_forum['upfp'] ? '>=' : '<=') . " '${id}'")->fetchColumn() / $user->config->kmess);

echo $view->render(
    'forum::show_post',
    [
        'title'         => _t('Show post'),
        'page_title'    => _t('Show post'),
        'post'          => $post,
        'topic'         => $them,
        'back_to_topic' => '/forum/?type=topic&id=' . $res['topic_id'] . '&amp;page=' . $page,
    ]
);
