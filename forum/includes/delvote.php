<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

if ($rights == 3 || $rights >= 6) {
    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);

    $topic_vote = $db->query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type`='1' AND `topic` = '$id'")->fetchColumn();
    require('../incfiles/head.php');

    if ($topic_vote == 0) {
        echo functions::display_error(_t('Wrong data'));
        require('../incfiles/end.php');
        exit;
    }

    if (isset($_GET['yes'])) {
        $db->exec("DELETE FROM `cms_forum_vote` WHERE `topic` = '$id'");
        $db->exec("DELETE FROM `cms_forum_vote_users` WHERE `topic` = '$id'");
        $db->exec("UPDATE `forum` SET  `realid` = '0'  WHERE `id` = '$id'");
        echo _t('Poll deleted') . '<br /><a href="' . $_SESSION['prd'] . '">' . _t('Continue') . '</a>';
    } else {
        echo '<p>' . _t('Do you really want to delete a poll?') . '</p>';
        echo '<p><a href="?act=delvote&amp;id=' . $id . '&amp;yes">' . _t('Delete') . '</a><br />';
        echo '<a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">' . _t('Cancel') . '</a></p>';
        $_SESSION['prd'] = htmlspecialchars(getenv("HTTP_REFERER"));
    }
} else {
    header('location: ../index.php?err');
}
