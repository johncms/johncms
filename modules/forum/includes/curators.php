<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO                        $db
 * @var Johncms\Api\ToolsInterface $tools
 * @var Johncms\Api\UserInterface  $user
 */

if ($user->rights >= 7) {
    $req = $db->query("SELECT * FROM `forum_topic` WHERE `id` = '${id}'");

    if (! $req->rowCount() || $user->rights < 7) {
        echo $tools->displayError(_t('Topic has been deleted or does not exists'));
        echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
        exit;
    }

    $topic = $req->fetch();
    $req = $db->query("SELECT `forum_messages`.*, `users`.`id`
        FROM `forum_messages` LEFT JOIN `users` ON `forum_messages`.`user_id` = `users`.`id`
        WHERE `forum_messages`.`topic_id`='${id}' AND `users`.`rights` < 6 AND `users`.`rights` != 3 GROUP BY `forum_messages`.`user_id` ORDER BY `forum_messages`.`user_name`");
    $total = $req->rowCount();
    echo '<div class="phdr"><a href="?type=topic&amp;id=' . $id . '&amp;start=' . $start . '"><b>' . _t('Forum') . '</b></a> | ' . _t('Curators') . '</div>' .
        '<div class="bmenu">' . $topic['name'] . '</div>';
    $curators = [];
    $users = ! empty($topic['curators']) ? unserialize($topic['curators'], ['allowed_classes' => false]) : [];

    if (isset($_POST['submit'])) {
        $users = $_POST['users'] ?? [];
        if (! is_array($users)) {
            $users = [];
        }
    }

    if ($total > 0) {
        echo '<form action="?act=curators&amp;id=' . $id . '&amp;start=' . $start . '" method="post">';
        $i = 0;

        while ($res = $req->fetch()) {
            $checked = array_key_exists($res['user_id'], $users) ? true : false;

            if ($checked) {
                $curators[$res['user_id']] = $res['user_name'];
            }

            echo($i++ % 2 ? '<div class="list2">' : '<div class="list1">') .
                '<input type="checkbox" name="users[' . $res['user_id'] . ']" value="' . $res['user_name'] . '"' . ($checked ? ' checked="checked"' : '') . '/>&#160;' .
                '<a href="../profile/?user=' . $res['user_id'] . '">' . $res['user_name'] . '</a></div>';
        }

        echo '<div class="gmenu"><input type="submit" value="' . _t('Assign') . '" name="submit" /></div></form>';

        if (isset($_POST['submit'])) {
            $db->exec('UPDATE `forum_topic` SET `curators`=' . $db->quote(serialize($curators)) . " WHERE `id` = '${id}'");
        }
    } else {
        echo $tools->displayError(_t('The list is empty'));
    }
    echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>' .
        '<p><a href="?type=topic&amp;id=' . $id . '&amp;start=' . $start . '">' . _t('Back') . '</a></p>';
}
