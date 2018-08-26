<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                Mobile Content Management System                    //
// Project site:          http://johncms.com                                  //
// Support site:          http://gazenwagen.com                               //
////////////////////////////////////////////////////////////////////////////////
// Lead Developer:        Oleg Kasyanov   (AlkatraZ)  alkatraz@gazenwagen.com //
// Development Team:      Eugene Ryabinin (john77)    john77@gazenwagen.com   //
//                        Dmitry Liseenko (FlySelf)   flyself@johncms.com     //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');

if ($user_id) {
    $topic = $db->query("SELECT COUNT(*) FROM `forum` WHERE `type`='t' AND `id` = '$id' AND `edit` != '1'")->fetchColumn();
    $vote = abs(intval($_POST['vote']));
    $topic_vote = $db->query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type` = '2' AND `id` = '$vote' AND `topic` = '$id'")->fetchColumn();
    $vote_user = $db->query("SELECT COUNT(*) FROM `cms_forum_vote_users` WHERE `user` = '$user_id' AND `topic` = '$id'")->fetchColumn();
    require('../incfiles/head.php');
    if ($topic_vote == 0 || $vote_user > 0 || $topic == 0) {
        echo functions::display_error($lng['error_wrong_data']);
        require('../incfiles/end.php');
        exit;
    }
    $db->exec("INSERT INTO `cms_forum_vote_users` SET `topic` = '$id', `user` = '$user_id', `vote` = '$vote'");
    $db->exec("UPDATE `cms_forum_vote` SET `count` = count + 1 WHERE id = '$vote'");
    $db->exec("UPDATE `cms_forum_vote` SET `count` = count + 1 WHERE topic = '$id' AND `type` = '1'");
    echo $lng_forum['vote_accepted'] . '<br /><a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">' . $lng['back'] . '</a>';
} else {
    echo functions::display_error($lng['access_guest_forbidden']);
}
