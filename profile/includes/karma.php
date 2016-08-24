<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

$textl = _td('Karma');
require('../incfiles/head.php');

if ($set_karma['on']) {
    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);

    switch ($mod) {
        case 'vote':
            // Отдаем голос за пользователя
            if (!$datauser['karma_off'] && !$ban) {
                $error = [];

                if ($user['rights'] && $set_karma['adm']) {
                    $error[] = _td('It is forbidden to vote for administration');
                }

                if ($user['ip'] == $ip) {
                    $error[] = _td('Cheating karma is forbidden');
                }

                if ($datauser['total_on_site'] < $set_karma['karma_time'] || $datauser['postforum'] < $set_karma['forum']) {
                    $error[] = sprintf(
                        _td('Users can take part in voting if they have stayed on a site not less %s and their score on the forum %d posts.'),
                        ($set_karma['time'] ? ($set_karma['karma_time'] / 3600) . _td('hours') : ($set_karma['karma_time'] / 86400) . _td('days')),
                        $set_karma['forum']
                    );
                }

                $count = $db->query("SELECT COUNT(*) FROM `karma_users` WHERE `user_id` = '$user_id' AND `karma_user` = '" . $user['id'] . "' AND `time` > '" . (time() - 86400) . "'")->fetchColumn();

                if ($count) {
                    $error[] = _td('You can vote for single user just one time for 24 hours"');
                }

                $sum = $db->query("SELECT SUM(`points`) FROM `karma_users` WHERE `user_id` = '$user_id' AND `time` >= '" . $datauser['karma_time'] . "'")->fetchColumn();

                if (($set_karma['karma_points'] - $sum) <= 0) {
                    $error[] = sprintf(_td('You have exceeded the limit of votes. New voices will be added %s'), date('d.m.y в H:i:s', ($datauser['karma_time'] + 86400)));
                }

                if ($error) {
                    echo functions::display_error($error, '<a href="?user=' . $user['id'] . '">' . _td('Back') . '</a>');
                } else {
                    if (isset($_POST['submit'])) {
                        $text = isset($_POST['text']) ? mb_substr(trim($_POST['text']), 0, 500) : '';
                        $type = intval($_POST['type']) ? 1 : 0;
                        $points = abs(intval($_POST['points']));

                        if (!$points || $points > ($set_karma['karma_points'] - $sum)) {
                            $points = 1;
                        }

                        $db->prepare('
                          INSERT INTO `karma_users` SET
                          `user_id` = ?,
                          `name` = ?,
                          `karma_user` = ?,
                          `points` = ?,
                          `type` = ?,
                          `time` = ?,
                          `text` = ?
                        ')->execute([
                            $user_id,
                            $login,
                            $user['id'],
                            $points,
                            $type,
                            time(),
                            $text,
                        ]);

                        $sql = $type ? "`karma_plus` = '" . ($user['karma_plus'] + $points) . "'" : "`karma_minus` = '" . ($user['karma_minus'] + $points) . "'";
                        $db->query("UPDATE `users` SET $sql WHERE `id` = " . $user['id']);
                        echo '<div class="gmenu">' . _td('You have successfully voted') . '!<br /><a href="?user=' . $user['id'] . '">' . _td('Continue') . '</a></div>';
                    } else {
                        echo '<div class="phdr"><b>' . _td('Vote for') . ' ' . $res['name'] . '</b>: ' . functions::checkout($user['name']) . '</div>' .
                            '<form action="?act=karma&amp;mod=vote&amp;user=' . $user['id'] . '" method="post">' .
                            '<div class="gmenu"><b>' . _td('Type of vote') . ':</b><br />' .
                            '<input name="type" type="radio" value="1" checked="checked"/> ' . _td('Positive') . '<br />' .
                            '<input name="type" type="radio" value="0"/> ' . _td('Negative') . '<br />' .
                            '<b>' . _td('Votes quantity') . ':</b><br />' .
                            '<select size="1" name="points">';

                        for ($i = 1; $i < ($set_karma['karma_points'] - $sum + 1); $i++) {
                            echo '<option value="' . $i . '">' . $i . '</option>';
                        }

                        echo '</select><b><br />' . _td('Comment') . ':</b><br />' .
                            '<input name="text" type="text" value=""/><br />' .
                            '<small>' . _td('Min. 2, Max. 500 characters') . '</small>' .
                            '<p><input type="submit" name="submit" value="' . _td('Vote') . '"/></p>' .
                            '</div></form>' .
                            '<div class="list2"><a href="?user=' . $user['id'] . '">' . _td('Profile') . '</a></div>';
                    }
                }
            } else {
                echo functions::display_error(_td('You are not allowed to vote for users'), '<a href="?user=' . $user['id'] . '">' . _td('Back') . '</a>');
            }

            break;

        case 'delete':
            // Удаляем отдельный голос
            if ($rights == 9) {
                $type = isset($_GET['type']) ? abs(intval($_GET['type'])) : null;
                $req = $db->query("SELECT * FROM `karma_users` WHERE `id` = '$id' AND `karma_user` = '" . $user['id'] . "'");

                if ($req->rowCount()) {
                    $res = $req->fetch();

                    if (isset($_GET['yes'])) {
                        $db->exec("DELETE FROM `karma_users` WHERE `id` = '$id'");

                        //TODO: Доработать калькуляцию
                        if ($res['type']) {
                            $sql = "`karma_plus` = '" . ($user['karma_plus'] > $res['points'] ? $user['karma_plus'] - $res['points'] : 0) . "'";
                        } else {
                            $sql = "`karma_minus` = '" . ($user['karma_minus'] > $res['points'] ? $user['karma_minus'] - $res['points'] : 0) . "'";
                        }

                        $db->exec("UPDATE `users` SET $sql WHERE `id` = " . $user['id']);
                        header('Location: ?act=karma&user=' . $user['id'] . '&type=' . $type);
                    } else {
                        echo '<div class="rmenu"><p>' . _td('Do you really want to delete comment?') . '<br/>' .
                            '<a href="?act=karma&amp;mod=delete&amp;user=' . $user['id'] . '&amp;id=' . $id . '&amp;type=' . $type . '&amp;yes">' . _td('Delete') . '</a> | ' .
                            '<a href="?act=karma&amp;user=' . $user['id'] . '&amp;type=' . $type . '">' . _td('Cancel') . '</a></p></div>';
                    }
                }
            }
            break;

        case 'clean':
            // Очищаем все голоса за пользователя
            if ($rights == 9) {
                if (isset($_GET['yes'])) {
                    $db->exec("DELETE FROM `karma_users` WHERE `karma_user` = " . $user['id']);
                    $db->query('OPTIMIZE TABLE `karma_users`');
                    $db->exec("UPDATE `users` SET `karma_plus` = '0', `karma_minus` = '0' WHERE `id` = " . $user['id']);
                    header('Location: ?user=' . $user['id']);
                } else {
                    echo '<div class="rmenu"><p>' . _td('Do you really want to delete all reviews about user?') . '<br/>' .
                        '<a href="?act=karma&amp;mod=clean&amp;user=' . $user['id'] . '&amp;yes">' . _td('Delete') . '</a> | ' .
                        '<a href="?act=karma&amp;user=' . $user['id'] . '">' . _td('Cancel') . '</a></p></div>';
                }
            }

            break;

        case 'new':
            // Список новых отзывов (комментариев)
            echo '<div class="phdr"><a href="?act=karma&amp;type=2"><b>' . _td('Karma') . '</b></a> | ' . _td('New responses') . '</div>';
            $total = $db->query("SELECT COUNT(*) FROM `karma_users` WHERE `karma_user` = '$user_id' AND `time` > " . (time() - 86400))->fetchColumn();

            if ($total) {
                $req = $db->query("SELECT * FROM `karma_users` WHERE `karma_user` = '$user_id' AND `time` > " . (time() - 86400) . " ORDER BY `time` DESC LIMIT $start, $kmess");

                while ($res = $req->fetch()) {
                    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                    echo $res['type'] ? '<span class="green">+' . $res['points'] . '</span> ' : '<span class="red">-' . $res['points'] . '</span> ';
                    echo $user_id == $res['user_id'] || !$res['user_id'] ? '<b>' . $res['name'] . '</b>' : '<a href="?user=' . $res['user_id'] . '"><b>' . $res['name'] . '</b></a>';
                    echo ' <span class="gray">(' . functions::display_date($res['time']) . ')</span>';
                    if (!empty($res['text'])) {
                        echo '<div class="sub">' . functions::checkout($res['text']) . '</div>';
                    }
                    echo '</div>';
                    ++$i;
                }
            } else {
                echo '<div class="menu"><p>' . _td('The list is empty') . '</p></div>';
            }
            echo '<div class="phdr">' . _td('Total') . ': ' . $total . '</div>';

            if ($total > $kmess) {
                echo '<p>' . functions::display_pagination('?act=karma&amp;mod=new&amp;', $start, $total, $kmess) . '</p>' .
                    '<p><form action="?act=karma&amp;mod=new" method="post">' .
                    '<input type="text" name="page" size="2"/>' .
                    '<input type="submit" value="' . _td('To Page') . ' &gt;&gt;"/></form></p>';
            }

            echo '<p><a href="index.php">' . _td('Profile') . '</a></p>';
            break;

        default:
            // Главная страница Кармы, список отзывов
            $type = isset($_GET['type']) ? abs(intval($_GET['type'])) : 0;
            $menu = [
                ($type == 2 ? '<b>' . _td('All') . '</b>' : '<a href="?act=karma&amp;user=' . $user['id'] . '&amp;type=2">' . _td('All') . '</a>'),
                ($type == 1 ? '<b>' . _td('Positive') . '</b>' : '<a href="?act=karma&amp;user=' . $user['id'] . '&amp;type=1">' . _td('Positive') . '</a>'),
                (!$type ? '<b>' . _td('Negative') . '</b>' : '<a href="?act=karma&amp;user=' . $user['id'] . '">' . _td('Negative') . '</a>'),
            ];
            echo '<div class="phdr"><a href="?user=' . $user['id'] . '"><b>' . _td('Profile') . '</b></a> | ' . _td('Karma') . '</div>' .
                '<div class="topmenu">' . functions::display_menu($menu) . '</div>' .
                '<div class="user"><p>' . functions::display_user($user, ['iphide' => 1,]) . '</p></div>';
            $karma = $user['karma_plus'] - $user['karma_minus'];

            if ($karma > 0) {
                $images = ($user['karma_minus'] ? ceil($user['karma_plus'] / $user['karma_minus']) : $user['karma_plus']) > 10 ? '2' : '1';
                echo '<div class="gmenu">';
            } else {
                if ($karma < 0) {
                    $images = ($user['karma_plus'] ? ceil($user['karma_minus'] / $user['karma_plus']) : $user['karma_minus']) > 10 ? '-2' : '-1';
                    echo '<div class="rmenu">';
                } else {
                    $images = 0;
                    echo '<div class="menu">';
                }
            }

            echo '<table  width="100%"><tr><td width="22" valign="top"><img src="' . $set['homeurl'] . '/images/k_' . $images . '.gif"/></td><td>' .
                '<b>' . _td('Karma') . ' (' . $karma . ')</b>' .
                '<div class="sub">' .
                '<span class="green">' . _td('For') . ' (' . $user['karma_plus'] . ')</span> | ' .
                '<span class="red">' . _td('Against') . ' (' . $user['karma_minus'] . ')</span>';
            echo '</div></td></tr></table></div>';
            $total = $db->query("SELECT COUNT(*) FROM `karma_users` WHERE `karma_user` = '" . $user['id'] . "'" . ($type == 2 ? "" : " AND `type` = '$type'"))->fetchColumn();

            if ($total) {
                $req = $db->query("SELECT * FROM `karma_users` WHERE `karma_user` = '" . $user['id'] . "'" . ($type == 2 ? "" : " AND `type` = '$type'") . " ORDER BY `time` DESC LIMIT $start, $kmess");
                $i = 0;

                while ($res = $req->fetch()) {
                    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                    echo $res['type'] ? '<span class="green">+' . $res['points'] . '</span> ' : '<span class="red">-' . $res['points'] . '</span> ';
                    echo $user_id == $res['user_id'] || !$res['user_id'] ? '<b>' . $res['name'] . '</b>' : '<a href="?user=' . $res['user_id'] . '"><b>' . $res['name'] . '</b></a>';
                    echo ' <span class="gray">(' . functions::display_date($res['time']) . ')</span>';

                    if ($rights == 9) {
                        echo ' <span class="red"><a href="?act=karma&amp;mod=delete&amp;user=' . $user['id'] . '&amp;id=' . $res['id'] . '&amp;type=' . $type . '">[X]</a></span>';
                    }

                    if (!empty($res['text'])) {
                        echo '<br />' . functions::smileys(functions::checkout($res['text']));
                    }

                    echo '</div>';
                    ++$i;
                }
            } else {
                echo '<div class="menu"><p>' . _td('The list is empty') . '</p></div>';
            }

            echo '<div class="phdr">' . _td('Total') . ': ' . $total . '</div>';

            if ($total > $kmess) {
                echo '<div class="topmenu">' . functions::display_pagination('?act=karma&amp;user=' . $user['id'] . '&amp;type=' . $type . '&amp;', $start, $total, $kmess) . '</div>' .
                    '<p><form action="?act=karma&amp;user=' . $user['id'] . '&amp;type=' . $type . '" method="post">' .
                    '<input type="text" name="page" size="2"/>' .
                    '<input type="submit" value="' . _td('To Page') . ' &gt;&gt;"/></form></p>';
            }

            echo '<p>' . ($rights == 9 ? '<a href="?act=karma&amp;user=' . $user['id'] . '&amp;mod=clean">' . _td('Reset Karma') . '</a><br />' : '') .
                '<a href="?user=' . $user['id'] . '">' . _td('Profile') . '</a></p>';
    }
}
