<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\FileInfo;
use Johncms\UserProperties;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 */

if (empty($_GET['id'])) {
    http_response_code(404);
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('Show post'),
            'type'          => 'alert-danger',
            'message'       => __('Wrong data'),
            'back_url'      => '/forum/',
            'back_url_name' => __('Forum'),
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
            'title'         => __('Show post'),
            'type'          => 'alert-danger',
            'message'       => __('Wrong data'),
            'back_url'      => '/forum/',
            'back_url_name' => __('Forum'),
        ]
    );
    exit;
}
// Запрос темы
$them = $db->query("SELECT * FROM `forum_topic` WHERE `id` = '" . $res['topic_id'] . "'")->fetch();

$post = [];
$res['post_url'] = '/forum/?act=show_post&amp;id=' . $res['id'];

$user_properties = new UserProperties();
$user_data = $user_properties->getFromArray($res);
$res = array_merge($res, $user_data);

$res['reply_url'] = '';
$res['quote_url'] = '';
if ($user->isValid() && $user->id != $res['user_id']) {
    $res['reply_url'] = '/forum/?act=say&amp;type=reply&amp;id=' . $res['id'] . '&amp;start=' . $start;
    $res['quote_url'] = '/forum/?act=say&amp;type=reply&amp;id=' . $res['id'] . '&amp;start=' . $start . '&amp;cyt';
}

$res['post_time'] = $tools->displayDate($res['date']);
$res['edit_time'] = $res['edit_count'] ? $tools->displayDate($res['edit_time']) : '';

$text = $res['text'];
$text = $tools->checkout($text, 1, 1);
$text = $tools->smilies($text, $res['rights'] ? 1 : 0);
$res['post_text'] = $text;

// Если есть прикрепленный файл, выводим его описание
$freq = $db->query("SELECT * FROM `cms_forum_files` WHERE `post` = '" . $res['id'] . "'");
$res['files'] = [];
if ($freq->rowCount()) {
    while ($fres = $freq->fetch()) {
        $file_params = [];
        $file_info = new FileInfo(UPLOAD_PATH . 'forum/attach/' . $fres['filename']);
        if (! $file_info->isFile()) {
            continue;
        }
        $file_params['file_size'] = format_size($file_info->getSize());
        $file_params['file_preview'] = '';
        $file_params['file_url'] = '/forum/?act=file&amp;id=' . $fres['id'];
        $file_params['delete_url'] = '/forum/?act=editpost&amp;do=delfile&amp;fid=' . $fres['id'] . '&amp;id=' . $res['id'];
        if ($file_info->isImage()) {
            $file_params['file_preview'] = '/assets/modules/forum/thumbinal.php?file=' . (urlencode($fres['filename']));
        }

        $res['files'][] = array_merge($fres, $file_params);
    }
}

$post = $res;

// Вычисляем, на какой странице сообщение?
$page = ceil($db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id` = '" . $res['topic_id'] . "' AND `id` " . ($set_forum['upfp'] ? '>=' : '<=') . " '${id}'")->fetchColumn() / $user->config->kmess);

$canonical = $config['homeurl'] . '/forum/?type=topic&id=' . $res['topic_id'];
if ($page > 1) {
    $canonical .= '&page=' . $page;
}

$view->addData(
    [
        'canonical'  => $canonical,
        'title'      => __('Show post'),
        'page_title' => __('Show post'),
    ]
);

echo $view->render(
    'forum::show_post',
    [
        'post'          => $post,
        'topic'         => $them,
        'back_to_topic' => '/forum/?type=topic&id=' . $res['topic_id'] . '&page=' . $page,
    ]
);
