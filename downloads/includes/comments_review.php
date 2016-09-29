<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

require_once '../incfiles/head.php';

// Обзор комментариев
if (!$set['mod_down_comm'] && $rights < 7) {
    echo _t('Comments are disabled') . '<a href="?">' . _t('Downloads') . '</a>';
    exit;
}

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);
$textl = _t('Review comments');

if (!$set['mod_down_comm']) {
    echo '<div class="rmenu">' . _t('Comments are disabled') . '</div>';
}

echo '<div class="phdr"><a href="?"><b>' . _t('Downloads') . '</b></a> | ' . $textl . '</div>';
$total = $db->query("SELECT COUNT(*) FROM `download__comments`")->fetchColumn();

if ($total) {
    $req = $db->query("SELECT `download__comments`.*, `download__comments`.`id` AS `cid`, `user__`.`rights`, `user__`.`last_visit`, `user__`.`sex`, `user__`.`status`, `user__`.`join_date`, `user__`.`id`, `download__files`.`rus_name`
	FROM `download__comments` LEFT JOIN `user__` ON `download__comments`.`user_id` = `user__`.`id` LEFT JOIN `download__files` ON `download__comments`.`sub_id` = `download__files`.`id` ORDER BY `download__comments`.`time` DESC " . $db->pagination());
    $i = 0;

    // Навигация
    if ($total > $kmess) {
        echo '<div class="topmenu">' . Functions::displayPagination('?act=review_comments&amp;', $start, $total, $kmess) . '</div>';
    }

    // Выводим список
    while ($res = $req->fetch()) {
        $text = '';
        echo ($i++ % 2) ? '<div class="list2">' : '<div class="list1">';
        $text = ' <span class="gray">(' . Functions::displayDate($res['time']) . ')</span>';
        $post = htmlspecialchars($res['text'], 1, 1);

        if (App::user()->settings['smilies']) {
            $post = Functions::smilies($post, $res['rights'] >= 1 ? 1 : 0);
        }

        $subtext = '<a href="index.php?act=view&amp;id=' . $res['sub_id'] . '">' . htmlspecialchars($res['rus_name']) . '</a> | <a href="?act=comments&amp;id=' . $res['sub_id'] . '">' . _t('Comments') . '</a>';
        $attributes = unserialize($res['attributes']);
        $res['nickname'] = $attributes['author_name'];
        $res['ip'] = $attributes['author_ip'];
        $res['ip_via_proxy'] = isset($attributes['author_ip_via_proxy']) ? $attributes['author_ip_via_proxy'] : 0;
        $res['user_agent'] = $attributes['author_browser'];

        if (isset($attributes['edit_count'])) {
            $post .= '<br><span class="gray"><small>Изменен: <b>' . $attributes['edit_name'] . '</b>' .
                ' (' . functions::displayDate($attributes['edit_time']) . ') <b>' .
                '[' . $attributes['edit_count'] . ']</b></small></span>';
        }

        if (!empty($res['reply'])) {
            $reply = htmlspecialchars($res['reply'], 1, 1);

            if (App::user()->settings['smilies']) {
                $reply = functions::smilies($reply, $attributes['reply_rights'] >= 1 ? 1 : 0);
            }

            $post .= '<div class="reply"><small>' .
                //TODO: Переделать ссылку
                '<a href="' . App::cfg()->sys->homeurl . '?profile.php?user=' . $attributes['reply_id'] . '"><b>' . $attributes['reply_name'] . '</b></a>' .
                ' (' . functions::displayDate($attributes['reply_time']) . ')</small><br>' . $reply . '</div>';
        }

        $arg = [
            'header' => $text,
            'body'   => $post,
            'sub'    => $subtext,
        ];

        echo functions::displayUser($res, $arg) . '</div>';
    }
} else {
    echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
}

echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

// Навигация
if ($total > $kmess) {
    echo '<div class="topmenu">' . Functions::displayPagination('?act=review_comments&amp;', $start, $total, $kmess) . '</div>' .
        '<p><form action="?" method="get">' .
        '<input type="hidden" value="review_comments" name="act" />' .
        '<input type="text" name="page" size="2"/><input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
}

echo '<p><a href="?">' . _t('Downloads') . '</a></p>';
require_once '../incfiles/end.php';
