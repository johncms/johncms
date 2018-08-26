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
if ($rights == 3 || $rights >= 6) {
    $topic = $db->query("SELECT COUNT(*) FROM `forum` WHERE `type`='t' AND `id`='$id' AND `edit` != '1'")->fetchColumn();
    $topic_vote = $db->query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type`='1' AND `topic`='$id'")->fetchColumn();
    require_once('../incfiles/head.php');
    if ($topic_vote != 0 || $topic == 0) {
        echo functions::display_error($lng['error_wrong_data'], '<a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">' . $lng['back'] . '</a>');
        require('../incfiles/end.php');
        exit;
    }
    if (isset($_POST['submit'])) {
        $vote_name = mb_substr(trim($_POST['name_vote']), 0, 50);
        if (!empty($vote_name) && !empty($_POST[0]) && !empty($_POST[1]) && !empty($_POST['count_vote'])) {
            $stmt = $db->prepare("INSERT INTO `cms_forum_vote` SET
                `name` = ?,
                `time` = ?,
                `type` = ?,
                `topic`= ?
            ");
            $stmt->execute([
                $vote_name,
                time(),
                1,
                $id
            ]);
            $db->exec("UPDATE `forum` SET  `realid` = '1'  WHERE `id` = '$id'");
            $vote_count = abs(intval($_POST['count_vote']));
            if ($vote_count > 20) {
                $vote_count = 20;
            } else if ($vote_count < 2) {
                $vote_count = 2;
            }
            for ($vote = 0; $vote < $vote_count; $vote++) {
                $text = mb_substr(trim($_POST[$vote]), 0, 30);
                if (empty($text)) {
                    continue;
                }
                $stmt->execute([
                    $text,
                    0,
                    2,
                    $id
                ]);
            }
            $stmt = null;
            echo $lng_forum['voting_added'] . '<br /><a href="?id=' . $id . '">' . $lng['continue'] . '</a>';
        } else
            echo $lng['error_empty_fields'] . '<br /><a href="?act=addvote&amp;id=' . $id . '">' . $lng['repeat'] . '</a>';
    } else {
        echo '<form action="index.php?act=addvote&amp;id=' . $id . '" method="post">' .
            '<br />' . $lng_forum['voting'] . ':<br/>' .
            '<input type="text" size="20" maxlength="150" name="name_vote" value="' . _e($_POST['name_vote']) . '"/><br/>';
        if (isset($_POST['plus']))
            ++$_POST['count_vote'];
        elseif (isset($_POST['minus']))
            --$_POST['count_vote'];
        if ($_POST['count_vote'] < 2 || empty($_POST['count_vote']))
            $_POST['count_vote'] = 2;
        elseif ($_POST['count_vote'] > 20)
            $_POST['count_vote'] = 20;
        for ($vote = 0; $vote < $_POST['count_vote']; $vote++) {
            echo $lng_forum['answer'] . ' ' . ($vote + 1) . '(max. 50): <br/><input type="text" name="' . $vote . '" value="' . _e($_POST[$vote]) . '"/><br/>';
        }
        echo '<input type="hidden" name="count_vote" value="' . abs(intval($_POST['count_vote'])) . '"/>';
        echo ($_POST['count_vote'] < 20) ? '<br/><input type="submit" name="plus" value="' . $lng_forum['add_answer'] . '"/>' : '';
        echo $_POST['count_vote'] > 2 ? '<input type="submit" name="minus" value="' . $lng_forum['delete_last'] . '"/><br/>' : '<br/>';
        echo '<p><input type="submit" name="submit" value="' . $lng['save'] . '"/></p></form>';
        echo '<a href="index.php?id=' . $id . '">' . $lng['back'] . '</a>';
    }
} else {
    header('location: ' . $homeurl . '/?err'); exit;
}
