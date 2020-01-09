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

$foundUser = (array) $foundUser;

// История активности
$title = _t('Activity') . ' - ' . htmlspecialchars($foundUser['name']);

$nav_chain->add(($foundUser['id'] !== $user->id ? _t('Profile') : _t('My Profile')), '?user=' . $foundUser['id']);
$nav_chain->add($title);

$data = [];
$data['filters'] = [
    'messages' => [
        'name'   => _t('Messages'),
        'url'    => '?act=activity&amp;user=' . $foundUser['id'],
        'active' => ! $mod,
    ],
    'topic'    => [
        'name'   => _t('Themes'),
        'url'    => '?act=activity&amp;mod=topic&amp;user=' . $foundUser['id'],
        'active' => $mod === 'topic',
    ],
    'comments' => [
        'name'   => _t('Comments'),
        'url'    => '?act=activity&amp;mod=comments&amp;user=' . $foundUser['id'],
        'active' => $mod === 'comments',
    ],
];

$activity = [];

switch ($mod) {
    case 'comments':
        // Список сообщений в Гостевой
        $total = $db->query("SELECT COUNT(*) FROM `guest` WHERE `user_id` = '" . $foundUser['id'] . "'" . ($user->rights >= 1 ? '' : " AND `adm` = '0'"))->fetchColumn();
        $req = $db->query("SELECT * FROM `guest` WHERE `user_id` = '" . $foundUser['id'] . "'" . ($user->rights >= 1 ? '' : " AND `adm` = '0'") . " ORDER BY `id` DESC LIMIT ${start}, " . $user->config->kmess);
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
        $total = $db->query("SELECT COUNT(*) FROM `forum_topic` WHERE `user_id` = '" . $foundUser['id'] . "'" . ($user->rights >= 7 ? '' : " AND (`deleted`!='1' OR deleted IS NULL)"))->fetchColumn();
        $req = $db->query("SELECT * FROM `forum_topic` WHERE `user_id` = '" . $foundUser['id'] . "'" . ($user->rights >= 7 ? '' : " AND (`deleted`!='1' OR deleted IS NULL)") . " ORDER BY `id` DESC LIMIT ${start}, " . $user->config->kmess);

        if ($req->rowCount()) {
            while ($res = $req->fetch()) {
                // Надо будет переделать это, но потом.
                $post = $db->query("SELECT * FROM `forum_messages` WHERE `topic_id` = '" . $res['id'] . "'" . ($user->rights >= 7 ? '' : " AND (`deleted`!='1' OR deleted IS NULL)") . ' ORDER BY `id` ASC LIMIT 1')->fetch();
                $section = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '" . $res['section_id'] . "'")->fetch();
                $category = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '" . $section['parent'] . "'")->fetch();
                $text = mb_substr($post['text'], 0, 300);
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
        $total = $db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `user_id` = '" . $foundUser['id'] . "'" . ($user->rights >= 7 ? '' : " AND (`deleted`!='1' OR deleted IS NULL)"))->fetchColumn();
        $req = $db->query(
            "SELECT * FROM `forum_messages` WHERE `user_id` = '" . $foundUser['id'] . "' " . ($user->rights >= 7 ? '' : " AND (`deleted`!='1' OR deleted IS NULL)") . " ORDER BY `id` DESC LIMIT ${start}, " . $user->config->kmess
        );

        if ($req->rowCount()) {
            while ($res = $req->fetch()) {
                $topic = $db->query("SELECT * FROM `forum_topic` WHERE `id` = '" . $res['topic_id'] . "'")->fetch();
                $section = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '" . $topic['section_id'] . "'")->fetch();
                $category = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '" . $section['parent'] . "'")->fetch();
                $text = mb_substr($res['text'], 0, 300);
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
$data['pagination'] = $tools->displayPagination('?act=activity' . ($mod ? '&amp;mod=' . $mod : '') . '&amp;user=' . $foundUser['id'] . '&amp;', $start, $total, $user->config->kmess);
$data['activity'] = $activity ?? [];

echo $view->render(
    'profile::activity',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
