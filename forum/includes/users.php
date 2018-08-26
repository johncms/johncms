<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');
require('../incfiles/head.php');
$topic_vote = $db->query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type` = '1' AND `topic` = '$id'")->fetchColumn();
if ($topic_vote == 0 || core::$user_rights < 7) {
    echo functions::display_error($lng['error_wrong_data']);
    require('../incfiles/end.php');
    exit;
} else {
    $topic_vote = $db->query("SELECT `name`, `time`, `count` FROM `cms_forum_vote` WHERE `type` = '1' AND `topic` = '$id' LIMIT 1")->fetch();
    echo '<div  class="phdr">' . $lng_forum['voting_users'] . ' &laquo;<b>' . htmlentities($topic_vote['name'], ENT_QUOTES, 'UTF-8') . '</b>&raquo;</div>';
    $total = $db->query("SELECT COUNT(*) FROM `cms_forum_vote_users` WHERE `topic`='$id'")->fetchColumn();
    $stmt = $db->query("SELECT `cms_forum_vote_users`.*, `users`.`rights`, `users`.`lastdate`, `users`.`name`, `users`.`sex`, `users`.`status`, `users`.`datereg`, `users`.`id`
    FROM `cms_forum_vote_users` LEFT JOIN `users` ON `cms_forum_vote_users`.`user` = `users`.`id`
    WHERE `cms_forum_vote_users`.`topic`='$id' LIMIT $start,$kmess");
    $i = 0;
    while ($res = $stmt->fetch()) {
        echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
        echo functions::display_user($res, array ('iphide' => 1));
        echo '</div>';
        ++$i;
    }
    if ($total == 0)
        echo '<div class="menu">' . $lng_forum['voting_users_empty'] . '</div>';
    echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
    if ($total > $kmess) {
        echo '<p>' . functions::display_pagination('index.php?act=users&amp;id=' . $id . '&amp;', $start, $total, $kmess) . '</p>' .
            '<p><form action="index.php?act=users&amp;id=' . $id . '" method="post">' .
            '<input type="text" name="page" size="2"/>' .
            '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
    }
    echo '<p><a href="index.php?id=' . $id . '">' . $lng_forum['to_topic'] . '</a></p>';
}

require('../incfiles/end.php');
