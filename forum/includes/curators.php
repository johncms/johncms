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

if (core::$user_rights >= 7) {
    $stmt = $db->query("SELECT * FROM `forum` WHERE `id` = '$id' AND `type` = 't' LIMIT 1");
    if (!$stmt->rowCount()) {
        echo functions::display_error($lng_forum['error_topic_deleted']);
        require('../incfiles/end.php');
        exit;
    }
    $topic = $stmt->fetch();
    $stmt = $db->query("SELECT `forum`.*, `users`.`id`
        FROM `forum` LEFT JOIN `users` ON `forum`.`user_id` = `users`.`id`
        WHERE `forum`.`refid`='$id' AND `users`.`rights` < 6 AND `users`.`rights` != 3 GROUP BY `forum`.`from` ORDER BY `forum`.`from`");
    $total = $stmt->rowCount();
    echo '<div class="phdr"><a href="index.php?id=' . $id . '&amp;start=' . $start . '"><b>' . $lng['forum'] . '</b></a> | ' . $lng_forum['curators'] . '</div>' .
         '<div class="bmenu">' . _e($topic['text']) . '</div>';
    $curators = array();
    $users = !empty($topic['curators']) ? unserialize($topic['curators']) : array();
    if (isset($_POST['submit'])) {
        $users = isset($_POST['users']) ? $_POST['users'] : array();
        if (!is_array($users)) $users = array();
    }
    if ($total > 0) {
        echo '<form action="index.php?act=curators&amp;id=' . $id . '&amp;start=' . $start . '" method="post">';
        $i = 0;
        while ($res = $stmt->fetch()) {
            $checked = array_key_exists($res['user_id'], $users) ? true : false;
            if ($checked) $curators[$res['user_id']] = $res['from'];
            echo ($i++ % 2 ? '<div class="list2">' : '<div class="list1">') .
                 '<input type="checkbox" name="users[' . $res['user_id'] . ']" value="' . $res['from'] . '"' . ($checked ? ' checked="checked"' : '') . '/>&#160;' .
                 '<a href="../users/profile.php?user=' . $res['user_id'] . '">' . $res['from'] . '</a></div>';
        }
        echo '<div class="gmenu"><input type="submit" value="' . $lng_forum['assign'] . '" name="submit" /></div></form>';
        if (isset($_POST['submit'])) {
            $stmt = $db->prepare("UPDATE `forum` SET `curators`= ? WHERE `id` = '$id' LIMIT 1");
            $stmt->execute([
                serialize($curators)
            ]);
        }

    } else
        echo functions::display_error($lng['list_empty']);
    echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>' .
         '<p><a href="index.php?id=' . $id . '&amp;start=' . $start . '">' . $lng['back'] . '</a></p>';
}