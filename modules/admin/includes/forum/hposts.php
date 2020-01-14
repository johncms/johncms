<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

defined('_IN_JOHNADM') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 * @var Johncms\NavChain $nav_chain
 * @var Johncms\System\Http\Request $request
 */


// Управление скрытыми постави форума
echo '<div class="phdr"><a href="?act=forum"><b>' . __('Forum Management') . '</b></a> | ' . __('Hidden posts') . '</div>';
$sort = '';
$link = '';

if (isset($_GET['tsort'])) {
    $sort = " AND `forum_messages`.`topic_id` = '" . abs((int) ($_GET['tsort'])) . "'";
    $link = '&amp;tsort=' . abs((int) ($_GET['tsort']));
    echo '<div class="bmenu">' . __('Filter by topic') . ' <a href="?act=forum&amp;mod=hposts">[x]</a></div>';
} elseif (isset($_GET['usort'])) {
    $sort = " AND `forum_messages`.`user_id` = '" . abs((int) ($_GET['usort'])) . "'";
    $link = '&amp;usort=' . abs((int) ($_GET['usort']));
    echo '<div class="bmenu">' . __('Filter by author') . ' <a href="?act=forum&amp;mod=hposts">[x]</a></div>';
}

if (isset($_POST['delpost'])) {
    if ($user->rights != 9) {
        echo $tools->displayError(__('Access forbidden'));
        echo $view->render('system::app/old_content', ['content' => ob_get_clean()]);
        exit;
    }

    $req = $db->query("SELECT `id` FROM `forum_messages` WHERE `deleted` = '1' ${sort}");

    while ($res = $req->fetch()) {
        $req_f = $db->query("SELECT * FROM `cms_forum_files` WHERE `post` = '" . $res['id'] . "'");

        if ($req_f->rowCount()) {
            while ($res_f = $req_f->fetch()) {
                // Удаляем файлы
                unlink('../files/forum/attach/' . $res_f['filename']);
            }
            $db->exec("DELETE FROM `cms_forum_files` WHERE `post` = '" . $res['id'] . "'");
        }
    }

    // Удаляем посты
    $db->exec("DELETE FROM `forum_messages` WHERE `deleted` = '1' ${sort}");

    header('Location: ?act=forum&mod=hposts');
} else {
    $total = $db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `deleted` = '1' ${sort}")->fetchColumn();

    if ($total > $user->config->kmess) {
        echo '<div class="topmenu">' . $tools->displayPagination('?act=forum&amp;mod=hposts&amp;', $start, $total, $user->config->kmess) . '</div>';
    }

    $req = $db->query(
        "SELECT `forum_messages`.*,
            `forum_messages`.`id` AS `fid`,
            `forum_messages`.`user_id` AS `id`,
            `forum_messages`.`user_name` AS `name`,
            `forum_messages`.`user_agent` AS `browser`,
            `users`.`rights`,
            `users`.`lastdate`,
            `users`.`sex`,
            `users`.`status`,
            `users`.`datereg`
            FROM `forum_messages` LEFT JOIN `users` ON `forum_messages`.`user_id` = `users`.`id`
            WHERE `forum_messages`.`deleted` = '1' ${sort} ORDER BY `forum_messages`.`id` DESC LIMIT " . $start . ',' . $user->config->kmess
    );

    if ($req->rowCount()) {
        $i = 0;

        while ($res = $req->fetch()) {
            $posttime = ' <span class="gray">(' . $tools->displayDate($res['time']) . ')</span>';
            $page = ceil(
                $db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id` = '" . $res['topic_id'] . "' AND `id` " . ($set_forum['upfp'] ? '>=' : '<=') . " '" . $res['fid'] . "'")->fetchColumn() / $user->config->kmess
            );
            $text = mb_substr($res['text'], 0, 500);
            $text = $tools->checkout($text, 1, 0);
            $text = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $text);
            $theme = $db->query("SELECT `id`, `name` FROM `forum_topic` WHERE `id` = '" . $res['topic_id'] . "'")->fetch();
            $text = '<b>' . $theme['name'] . '</b> <a href="../forum/?type=topic&id=' . $theme['id'] . '&amp;page=' . $page . '">&gt;&gt;</a><br>' . $text;
            $subtext = '<span class="gray">' . __('Filter') . ':</span> ';
            $subtext .= '<a href="?act=forum&amp;mod=hposts&amp;tsort=' . $theme['id'] . '">' . __('by topic') . '</a> | ';
            $subtext .= '<a href="?act=forum&amp;mod=hposts&amp;usort=' . $res['user_id'] . '">' . __('by author') . '</a>';
            echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
            echo $tools->displayUser(
                $res,
                [
                    'header' => $posttime,
                    'body'   => $text,
                    'sub'    => $subtext,
                ]
            );
            echo '</div>';
            ++$i;
        }

        if ($user->rights == 9) {
            echo '<form action="?act=forum&amp;mod=hposts' . $link . '" method="POST"><div class="rmenu"><input type="submit" name="delpost" value="' . __('Delete all') . '" /></div></form>';
        }
    } else {
        echo '<div class="menu"><p>' . __('The list is empty') . '</p></div>';
    }

    echo '<div class="phdr">' . __('Total') . ': ' . $total . '</div>';

    if ($total > $user->config->kmess) {
        echo '<div class="topmenu">' . $tools->displayPagination('?act=forum&amp;mod=hposts&amp;', $start, $total, $user->config->kmess) . '</div>' .
            '<p><form action="?act=forum&amp;mod=hposts" method="post">' .
            '<input type="text" name="page" size="2"/>' .
            '<input type="submit" value="' . __('To Page') . ' &gt;&gt;"/>' .
            '</form></p>';
    }
}
