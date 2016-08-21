<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

require('../incfiles/head.php');

if (core::$user_rights >= 7) {
    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);

    $req = $db->query("SELECT * FROM `forum` WHERE `id` = '$id' AND `type` = 't'");

    if (!$req->rowCount() || $rights < 7) {
        echo functions::display_error($lng_forum['error_topic_deleted']);
        require('../incfiles/end.php');
        exit;
    }

    $topic = $req->fetch();
    $req = $db->query("SELECT `forum`.*, `users`.`id`
        FROM `forum` LEFT JOIN `users` ON `forum`.`user_id` = `users`.`id`
        WHERE `forum`.`refid`='$id' AND `users`.`rights` < 6 AND `users`.`rights` != 3 GROUP BY `forum`.`from` ORDER BY `forum`.`from`");
    $total = $req->rowCount();
    echo '<div class="phdr"><a href="index.php?id=' . $id . '&amp;start=' . $start . '"><b>' . $lng['forum'] . '</b></a> | ' . $lng_forum['curators'] . '</div>' .
        '<div class="bmenu">' . $topic['text'] . '</div>';
    $curators = [];
    $users = !empty($topic['curators']) ? unserialize($topic['curators']) : [];

    if (isset($_POST['submit'])) {
        $users = isset($_POST['users']) ? $_POST['users'] : [];
        if (!is_array($users)) {
            $users = [];
        }
    }

    if ($total > 0) {
        echo '<form action="index.php?act=curators&amp;id=' . $id . '&amp;start=' . $start . '" method="post">';
        $i = 0;

        while ($res = $req->fetch()) {
            $checked = array_key_exists($res['user_id'], $users) ? true : false;

            if ($checked) {
                $curators[$res['user_id']] = $res['from'];
            }

            echo ($i++ % 2 ? '<div class="list2">' : '<div class="list1">') .
                '<input type="checkbox" name="users[' . $res['user_id'] . ']" value="' . $res['from'] . '"' . ($checked ? ' checked="checked"' : '') . '/>&#160;' .
                '<a href="../profile/?user=' . $res['user_id'] . '">' . $res['from'] . '</a></div>';
        }

        echo '<div class="gmenu"><input type="submit" value="' . $lng_forum['assign'] . '" name="submit" /></div></form>';

        if (isset($_POST['submit'])) {
            $db->exec("UPDATE `forum` SET `curators`=" . $db->quote(serialize($curators)) . " WHERE `id` = '$id'");
        }

    } else {
        echo functions::display_error($lng['list_empty']);
    }
    echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>' .
        '<p><a href="index.php?id=' . $id . '&amp;start=' . $start . '">' . $lng['back'] . '</a></p>';
}
