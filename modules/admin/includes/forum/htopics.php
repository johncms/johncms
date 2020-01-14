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

// Управление скрытыми темами форума
echo '<div class="phdr"><a href="?act=forum"><b>' . __('Forum Management') . '</b></a> | ' . __('Hidden topics') . '</div>';
$sort = '';
$link = '';

if (isset($_GET['usort'])) {
    $sort = " AND `forum_topic`.`user_id` = '" . abs((int) ($_GET['usort'])) . "'";
    $link = '&amp;usort=' . abs((int) ($_GET['usort']));
    echo '<div class="bmenu">' . __('Filter by author') . ' <a href="?act=forum&amp;mod=htopics">[x]</a></div>';
}

if (isset($_GET['rsort'])) {
    $sort = " AND `forum_topic`.`section_id` = '" . abs((int) ($_GET['rsort'])) . "'";
    $link = '&amp;rsort=' . abs((int) ($_GET['rsort']));
    echo '<div class="bmenu">' . __('Filter by section') . ' <a href="?act=forum&amp;mod=htopics">[x]</a></div>';
}

if (isset($_POST['deltopic'])) {
    if ($user->rights != 9) {
        echo $tools->displayError(__('Access forbidden'));
        echo $view->render('system::app/old_content', ['content' => ob_get_clean()]);
        exit;
    }

    $req = $db->query("SELECT `id` FROM `forum_topic` WHERE `deleted` = '1' ${sort}");

    while ($res = $req->fetch()) {
        $req_f = $db->query('SELECT * FROM `cms_forum_files` WHERE `topic` = ' . $res['id']);

        if ($req_f->rowCount()) {
            // Удаляем файлы
            while ($res_f = $req_f->fetch()) {
                unlink('../files/forum/attach/' . $res_f['filename']);
            }
            $db->exec('DELETE FROM `cms_forum_files` WHERE `topic` = ' . $res['id']);
        }
        // Удаляем посты
        $db->exec('DELETE FROM `forum_messages` WHERE `topic_id` = ' . $res['id']);
    }
    // Удаляем темы
    $db->exec("DELETE FROM `forum_topic` WHERE `deleted` = '1' ${sort}");

    header('Location: ?act=forum&mod=htopics');
} else {
    $total = $db->query("SELECT COUNT(*) FROM `forum_topic` WHERE `deleted` = '1' ${sort}")->fetchColumn();

    if ($total > $user->config->kmess) {
        echo '<div class="topmenu">' . $tools->displayPagination('?act=forum&amp;mod=htopics&amp;', $start, $total, $user->config->kmess) . '</div>';
    }

    $req = $db->query(
        "SELECT `forum_topic`.*,
            `forum_topic`.`id` AS `fid`,
            `forum_topic`.`name` AS `topic_name`,
            `forum_topic`.`user_id` AS `id`,
            `forum_topic`.`user_name` AS `name`,
            `users`.`rights`,
            `users`.`lastdate`,
            `users`.`sex`,
            `users`.`status`,
            `users`.`datereg`,
            `users`.`ip`,
            `users`.`browser`,
            `users`.`ip_via_proxy`
            FROM `forum_topic` LEFT JOIN `users` ON `forum_topic`.`user_id` = `users`.`id`
            WHERE `forum_topic`.`deleted` = '1' ${sort} ORDER BY `forum_topic`.`id` DESC LIMIT " . $start . ',' . $user->config->kmess
    );

    if ($req->rowCount()) {
        $i = 0;

        while ($res = $req->fetch()) {
            $subcat = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '" . $res['section_id'] . "'")->fetch();
            $cat = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '" . $subcat['parent'] . "'")->fetch();
            $ttime = '<span class="gray">(' . $tools->displayDate($res['mod_last_post_date']) . ')</span>';
            $text = '<a href="../forum/?type=topic&id=' . $res['fid'] . '"><b>' . $res['topic_name'] . '</b></a>';
            $text .= '<br><small><a href="../forum/?id=' . $cat['id'] . '">' . $cat['name'] . '</a> / <a href="../forum/?type=topics&id=' . $subcat['id'] . '">' . $subcat['name'] . '</a></small>';
            $subtext = '<span class="gray">' . __('Filter') . ':</span> ';
            $subtext .= '<a href="?act=forum&amp;mod=htopics&amp;rsort=' . $res['section_id'] . '">' . __('by section') . '</a> | ';
            $subtext .= '<a href="?act=forum&amp;mod=htopics&amp;usort=' . $res['user_id'] . '">' . __('by author') . '</a>';
            echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
            echo $tools->displayUser(
                $res,
                [
                    'header' => $ttime,
                    'body'   => $text,
                    'sub'    => $subtext,
                ]
            );
            echo '</div>';
            ++$i;
        }

        if ($user->rights == 9) {
            echo '<form action="?act=forum&amp;mod=htopics' . $link . '" method="POST">' .
                '<div class="rmenu">' .
                '<input type="submit" name="deltopic" value="' . __('Delete all') . '" />' .
                '</div></form>';
        }
    } else {
        echo '<div class="menu"><p>' . __('The list is empty') . '</p></div>';
    }

    echo '<div class="phdr">' . __('Total') . ': ' . $total . '</div>';

    if ($total > $user->config->kmess) {
        echo '<div class="topmenu">' . $tools->displayPagination('?act=forum&amp;mod=htopics&amp;', $start, $total, $user->config->kmess) . '</div>' .
            '<p><form action="?act=forum&amp;mod=htopics" method="post">' .
            '<input type="text" name="page" size="2"/>' .
            '<input type="submit" value="' . __('To Page') . ' &gt;&gt;"/>' .
            '</form></p>';
    }
}
