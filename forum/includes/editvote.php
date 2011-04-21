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
    $topic_vote = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type`='1' AND `topic`='$id'"), 0);
    require('../incfiles/head.php');
    if ($topic_vote == 0) {
        echo functions::display_error($lng['error_wrong_data']);
        require('../incfiles/end.php');
        exit;
    }
    if (isset($_GET['delvote']) && !empty($_GET['vote'])) {
        $vote = abs(intval($_GET['vote']));
        $totalvote = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type` = '2' AND `id` = '$vote' AND `topic` = '$id'"), 0);
        $countvote = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type` = '2' AND `topic` = '$id'"), 0);
        if ($countvote <= 2)
            header('location: ?act=editvote&id=' . $id . '');
        if ($totalvote != 0) {
            if (isset($_GET['yes'])) {
                mysql_query("DELETE FROM `cms_forum_vote` WHERE `id` = '$vote'");
                $countus = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_forum_vote_users` WHERE `vote` = '$vote' AND `topic` = '$id'"), 0);
                $topic_vote = mysql_fetch_array(mysql_query("SELECT `count` FROM `cms_forum_vote` WHERE `type` = '1' AND `topic` = '$id' LIMIT 1"));
                $totalcount = $topic_vote['count'] - $countus;
                mysql_query("UPDATE `cms_forum_vote` SET  `count` = '$totalcount'   WHERE `type` = '1' AND `topic` = '$id'");
                mysql_query("DELETE FROM `cms_forum_vote_users` WHERE `vote` = '$vote'");
                header('location: ?act=editvote&id=' . $id . '');
            } else {
                echo '<div class="rmenu"><p>' . $lng_forum['voting_variant_warning'] . '<br />' .
                    '<a href="index.php?act=editvote&amp;id=' . $id . '&amp;vote=' . $vote . '&amp;delvote&amp;yes">' . $lng['delete'] . '</a><br />' .
                    '<a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">' . $lng['cancel'] . '</a></p></div>';
            }
        } else {
            header('location: ?act=editvote&id=' . $id . '');
        }
    } else if (isset($_POST['submit'])) {
        $vote_name = mb_substr(trim($_POST['name_vote']), 0, 50);
        if (!empty($vote_name))
            mysql_query("UPDATE `cms_forum_vote` SET  `name` = '" . mysql_real_escape_string($vote_name) . "'  WHERE `topic` = '$id' AND `type` = '1'");
        $vote_result = mysql_query("SELECT `id` FROM `cms_forum_vote` WHERE `type`='2' AND `topic`='" . $id . "'");
        while ($vote = mysql_fetch_array($vote_result)) {
            if (!empty($_POST[$vote['id'] . 'vote'])) {
                $text = mb_substr(trim($_POST[$vote['id'] . 'vote']), 0, 30);
                mysql_query("UPDATE `cms_forum_vote` SET  `name` = '" . mysql_real_escape_string($text) . "'  WHERE `id` = '" . $vote['id'] . "'");
            }
        }
        $countvote = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type`='2' AND `topic`='" . $id . "'"), 0);
        for ($vote = $countvote; $vote < 20; $vote++) {
            if (!empty($_POST[$vote])) {
                $text = mb_substr(trim($_POST[$vote]), 0, 30);
                mysql_query("INSERT INTO `cms_forum_vote` SET `name` = '" . mysql_real_escape_string($text) . "',  `type` = '2', `topic` = '$id'");
            }
        }
        echo '<div class="gmenu"><p>' . $lng_forum['voting_changed'] . '<br /><a href="index.php?id=' . $id . '">' . $lng['continue'] . '</a></p></div>';
    } else {
        /*
        -----------------------------------------------------------------
        Форма редактирования опроса
        -----------------------------------------------------------------
        */
        $countvote = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type` = '2' AND `topic` = '$id'"), 0);
        $topic_vote = mysql_fetch_array(mysql_query("SELECT `name` FROM `cms_forum_vote` WHERE `type` = '1' AND `topic` = '$id' LIMIT 1"));
        echo '<div class="phdr"><a href="index.php?id=' . $id . '"><b>' . $lng['forum'] . '</b></a> | ' . $lng_forum['edit_vote'] . '</div>' .
            '<form action="index.php?act=editvote&amp;id=' . $id . '" method="post">' .
            '<div class="gmenu"><p>' .
            '<b>' . $lng_forum['voting'] . ':</b><br/>' .
            '<input type="text" size="20" maxlength="150" name="name_vote" value="' . htmlentities($topic_vote['name'], ENT_QUOTES, 'UTF-8') . '"/>' .
            '</p></div>' .
            '<div class="menu"><p>';
        $vote_result = mysql_query("SELECT `id`, `name` FROM `cms_forum_vote` WHERE `type` = '2' AND `topic` = '$id'");
        while ($vote = mysql_fetch_array($vote_result)) {
            echo $lng_forum['answer'] . ' ' . ($i + 1) . ' (max. 50): <br/>' .
                '<input type="text" name="' . $vote['id'] . 'vote" value="' . htmlentities($vote['name'], ENT_QUOTES, 'UTF-8') . '"/>';
            if ($countvote > 2)
                echo '&nbsp;<a href="index.php?act=editvote&amp;id=' . $id . '&amp;vote=' . $vote['id'] . '&amp;delvote">[x]</a>';
            echo '<br/>';
            ++$i;
        }
        if ($countvote < 20) {
            if (isset($_POST['plus']))
                ++$_POST['count_vote'];
            elseif (isset($_POST['minus']))
                --$_POST['count_vote'];
            if (empty($_POST['count_vote']))
                $_POST['count_vote'] = $countvote;
            elseif ($_POST['count_vote'] > 20)
                $_POST['count_vote'] = 20;
            for ($vote = $i; $vote < $_POST['count_vote']; $vote++) {
                echo 'Ответ ' . ($vote + 1) . '(max. 50): <br/><input type="text" name="' . $vote . '" value="' . functions::checkout($_POST[$vote]) . '"/><br/>';
            }
            echo '<input type="hidden" name="count_vote" value="' . abs(intval($_POST['count_vote'])) . '"/>' . ($_POST['count_vote'] < 20 ? '<input type="submit" name="plus" value="' . $lng['add'] . '"/>' : '')
                . ($_POST['count_vote'] - $countvote ? '<input type="submit" name="minus" value="' . $lng_forum['delete_last'] . '"/>' : '');
        }
        echo '</p></div><div class="gmenu">' .
            '<p><input type="submit" name="submit" value="' . $lng['save'] . '"/></p>' .
            '</div></form>' .
            '<div class="phdr"><a href="index.php?id=' . $id . '">' . $lng['cancel'] . '</a></div>';
    }
}
?>