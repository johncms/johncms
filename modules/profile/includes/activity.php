<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Users\User;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var User $user_data
 * @var User $user
 */

// История активности
$title = __('Activity') . ' - ' . htmlspecialchars($user_data->name);

$nav_chain->add(($user_data->id !== $user->id ? __('Profile') : __('My Profile')), '?user=' . $user_data->id);
$nav_chain->add($title);

$data = [];
$data['filters'] = [
    'messages' => [
        'name'   => __('Messages'),
        'url'    => '?act=activity&amp;user=' . $user_data->id,
        'active' => ! $mod,
    ],
    'topic'    => [
        'name'   => __('Themes'),
        'url'    => '?act=activity&amp;mod=topic&amp;user=' . $user_data->id,
        'active' => $mod === 'topic',
    ],
    'comments' => [
        'name'   => __('Comments'),
        'url'    => '?act=activity&amp;mod=comments&amp;user=' . $user_data->id,
        'active' => $mod === 'comments',
    ],
];

$activity = [];

switch ($mod) {
    case 'comments':
        // Список сообщений в Гостевой
        $total = $db->query("SELECT COUNT(*) FROM `guest` WHERE `user_id` = '" . $user_data->id . "'" . ($user->rights >= 1 ? '' : " AND `adm` = '0'"))->fetchColumn();
        $req = $db->query("SELECT * FROM `guest` WHERE `user_id` = '" . $user_data->id . "'" . ($user->rights >= 1 ? '' : " AND `adm` = '0'") . " ORDER BY `id` DESC LIMIT ${start}, " . $user->set_user->kmess);
        $data['item_type'] = 'comment';
        if ($req->rowCount()) {
            while ($res = $req->fetch()) {
                $res['text'] = $tools->checkout($res['text'], 1, 1);
                $res['display_date'] = $tools->displayDate($res['time']);
                $activity[] = $res;
            }
        }
        break;

    case 'topic':
        // Список тем Форума
        $data['item_type'] = 'topic';
        $total = $db->query("SELECT COUNT(*) FROM `forum_topic` WHERE `user_id` = '" . $user_data->id . "'" . ($user->rights >= 7 ? '' : " AND (`deleted`!='1' OR deleted IS NULL)"))->fetchColumn();
        $req = $db->query("SELECT * FROM `forum_topic` WHERE `user_id` = '" . $user_data->id . "'" . ($user->rights >= 7 ? '' : " AND (`deleted`!='1' OR deleted IS NULL)") . " ORDER BY `id` DESC LIMIT ${start}, " . $user->set_user->kmess);

        if ($req->rowCount()) {
            while ($res = $req->fetch()) {
                // Надо будет переделать это, но потом.
                $post = $db->query("SELECT * FROM `forum_messages` WHERE `topic_id` = '" . $res['id'] . "'" . ($user->rights >= 7 ? '' : " AND (`deleted`!='1' OR deleted IS NULL)") . ' ORDER BY `id` ASC LIMIT 1')->fetch();
                $section = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '" . $res['section_id'] . "'")->fetch();
                $category = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '" . $section['parent'] . "'")->fetch();
                $text = mb_strimwidth($post['text'], 0, 300, '...');
                $text = $tools->checkout($text, 2, 1);

                $row = [
                    'topic_url'     => '/forum/?type=topic&id=' . $res['id'],
                    'topic_name'    => $res['name'],
                    'topic_id'      => $res['id'],
                    'text'          => $text,
                    'display_date'  => $tools->displayDate($res['last_post_date']),
                    'category_name' => $category['name'],
                    'category_url'  => '/forum/?id=' . $category['id'],
                    'section_name'  => $section['name'],
                    'section_url'   => '/forum/?type=topics&id=' . $section['id'],
                ];
                $activity[] = $row;
            }
        }
        break;

    default:
        // Список постов Форума
        $data['item_type'] = 'message';
        $total = $db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `user_id` = '" . $user_data->id . "'" . ($user->rights >= 7 ? '' : " AND (`deleted`!='1' OR deleted IS NULL)"))->fetchColumn();
        $req = $db->query(
            "SELECT * FROM `forum_messages` WHERE `user_id` = '" . $user_data->id . "' " . ($user->rights >= 7 ? '' : " AND (`deleted`!='1' OR deleted IS NULL)") . " ORDER BY `id` DESC LIMIT ${start}, " . $user->set_user->kmess
        );

        if ($req->rowCount()) {
            while ($res = $req->fetch()) {
                $topic = $db->query("SELECT * FROM `forum_topic` WHERE `id` = '" . $res['topic_id'] . "'")->fetch();
                $section = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '" . $topic['section_id'] . "'")->fetch();
                $category = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '" . $section['parent'] . "'")->fetch();
                $text = mb_strimwidth($res['text'], 0, 300, '...');
                $text = $tools->checkout($text, 2, 1);
                $text = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $text);

                $row = [
                    'topic_url'     => '/forum/?type=topic&id=' . $topic['id'],
                    'topic_name'    => $topic['name'],
                    'topic_id'      => $topic['id'],
                    'text'          => $text,
                    'message_url'   => '/forum/?act=show_post&amp;id=' . $res['id'],
                    'display_date'  => $tools->displayDate($res['date']),
                    'category_name' => $category['name'],
                    'category_url'  => '/forum/?id=' . $category['id'],
                    'section_name'  => $section['name'],
                    'section_url'   => '/forum/?type=topics&id=' . $section['id'],
                ];
                $activity[] = $row;
            }
        }
}

$data['total'] = $total;
$data['pagination'] = $tools->displayPagination('?act=activity' . ($mod ? '&amp;mod=' . $mod : '') . '&amp;user=' . $user_data->id . '&amp;', $start, $total, $user->set_user->kmess);
$data['activity'] = $activity ?? [];

echo $view->render(
    'profile::activity',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
