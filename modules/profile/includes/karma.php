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

$textl = _t('Karma');
$set_karma = $config->karma;

if ($set_karma['on']) {
    /** @var PDO $db */
    $db = $container->get(PDO::class);

    /** @var Johncms\Api\UserInterface $user */
    $user = $container->get(Johncms\Api\UserInterface::class);

    /** @var Johncms\Api\ToolsInterface $tools */
    $tools = $container->get(Johncms\Api\ToolsInterface::class);

    switch ($mod) {
        case 'vote':
            // Отдаем голос за пользователя
            if (! $user->karma_off && empty($user->ban)) {
                $error = [];

                if ($foundUser['rights'] && $set_karma['adm']) {
                    $error[] = _t('It is forbidden to vote for administration');
                }

                if ($foundUser['ip'] == $container->get(Johncms\Api\EnvironmentInterface::class)->getIp()) {
                    $error[] = _t('Cheating karma is forbidden');
                }

                if ($user->total_on_site < $set_karma['karma_time'] || $user->postforum < $set_karma['forum']) {
                    $error[] = sprintf(
                        _t('Users can take part in voting if they have stayed on a site not less %s and their score on the forum %d posts.'),
                        ($set_karma['time'] ? ($set_karma['karma_time'] / 3600) . _t('hours') : ($set_karma['karma_time'] / 86400) . _t('days')),
                        $set_karma['forum']
                    );
                }

                $count = $db->query("SELECT COUNT(*) FROM `karma_users` WHERE `user_id` = '" . $user->id . "' AND `karma_user` = '" . $foundUser['id'] . "' AND `time` > '" . (time() - 86400) . "'")->fetchColumn();

                if ($count) {
                    $error[] = _t('You can vote for single user just one time for 24 hours"');
                }

                $sum = $db->query("SELECT SUM(`points`) FROM `karma_users` WHERE `user_id` = '" . $user->id . "' AND `time` >= '" . $user->karma_time . "'")->fetchColumn();

                if (($set_karma['karma_points'] - $sum) <= 0) {
                    $error[] = sprintf(_t('You have exceeded the limit of votes. New voices will be added %s'), date('d.m.y в H:i:s', ($user->karma_time + 86400)));
                }

                if ($error) {
                    echo $tools->displayError($error, '<a href="?user=' . $foundUser['id'] . '">' . _t('Back') . '</a>');
                } else {
                    if (isset($_POST['submit'])) {
                        $text = isset($_POST['text']) ? mb_substr(trim($_POST['text']), 0, 500) : '';
                        $type = (int) ($_POST['type']) ? 1 : 0;
                        $points = abs((int) ($_POST['points']));

                        if (! $points || $points > ($set_karma['karma_points'] - $sum)) {
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
                            $user->id,
                            $user->name,
                            $foundUser['id'],
                            $points,
                            $type,
                            time(),
                            $text,
                        ]);

                        $sql = $type ? "`karma_plus` = '" . ($foundUser['karma_plus'] + $points) . "'" : "`karma_minus` = '" . ($foundUser['karma_minus'] + $points) . "'";
                        $db->query("UPDATE `users` SET ${sql} WHERE `id` = " . $foundUser['id']);
                        echo '<div class="gmenu">' . _t('You have successfully voted') . '!<br /><a href="?user=' . $foundUser['id'] . '">' . _t('Continue') . '</a></div>';
                    } else {
                        echo '<div class="phdr"><b>' . _t('Vote for') . '</b>: ' . $tools->checkout($foundUser['name']) . '</div>' .
                            '<form action="?act=karma&amp;mod=vote&amp;user=' . $foundUser['id'] . '" method="post">' .
                            '<div class="gmenu"><b>' . _t('Type of vote') . ':</b><br />' .
                            '<input name="type" type="radio" value="1" checked="checked"/> ' . _t('Positive') . '<br />' .
                            '<input name="type" type="radio" value="0"/> ' . _t('Negative') . '<br />' .
                            '<b>' . _t('Votes quantity') . ':</b><br />' .
                            '<select size="1" name="points">';

                        for ($i = 1; $i < ($set_karma['karma_points'] - $sum + 1); $i++) {
                            echo '<option value="' . $i . '">' . $i . '</option>';
                        }

                        echo '</select><b><br />' . _t('Comment') . ':</b><br />' .
                            '<input name="text" type="text" value=""/><br />' .
                            '<small>' . _t('Min. 2, Max. 500 characters') . '</small>' .
                            '<p><input type="submit" name="submit" value="' . _t('Vote') . '"/></p>' .
                            '</div></form>' .
                            '<div class="list2"><a href="?user=' . $foundUser['id'] . '">' . _t('Profile') . '</a></div>';
                    }
                }
            } else {
                echo $tools->displayError(_t('You are not allowed to vote for users'), '<a href="?user=' . $foundUser['id'] . '">' . _t('Back') . '</a>');
            }
            break;

        case 'delete':
            // Удаляем отдельный голос
            if ($user->rights == 9) {
                $type = isset($_GET['type']) ? abs((int) ($_GET['type'])) : null;
                $req = $db->query("SELECT * FROM `karma_users` WHERE `id` = '${id}' AND `karma_user` = '" . $foundUser['id'] . "'");

                if ($req->rowCount()) {
                    $res = $req->fetch();

                    if (isset($_GET['yes'])) {
                        $db->exec("DELETE FROM `karma_users` WHERE `id` = '${id}'");

                        //TODO: Доработать калькуляцию
                        if ($res['type']) {
                            $sql = "`karma_plus` = '" . ($foundUser['karma_plus'] > $res['points'] ? $foundUser['karma_plus'] - $res['points'] : 0) . "'";
                        } else {
                            $sql = "`karma_minus` = '" . ($foundUser['karma_minus'] > $res['points'] ? $foundUser['karma_minus'] - $res['points'] : 0) . "'";
                        }

                        $db->exec("UPDATE `users` SET ${sql} WHERE `id` = " . $foundUser['id']);
                        header('Location: ?act=karma&user=' . $foundUser['id'] . '&type=' . $type);
                    } else {
                        echo '<div class="rmenu"><p>' . _t('Do you really want to delete comment?') . '<br>' .
                            '<a href="?act=karma&amp;mod=delete&amp;user=' . $foundUser['id'] . '&amp;id=' . $id . '&amp;type=' . $type . '&amp;yes">' . _t('Delete') . '</a> | ' .
                            '<a href="?act=karma&amp;user=' . $foundUser['id'] . '&amp;type=' . $type . '">' . _t('Cancel') . '</a></p></div>';
                    }
                }
            }
            break;

        case 'clean':
            // Очищаем все голоса за пользователя
            if ($user->rights == 9) {
                if (isset($_GET['yes'])) {
                    $db->exec('DELETE FROM `karma_users` WHERE `karma_user` = ' . $foundUser['id']);
                    $db->query('OPTIMIZE TABLE `karma_users`');
                    $db->exec("UPDATE `users` SET `karma_plus` = '0', `karma_minus` = '0' WHERE `id` = " . $foundUser['id']);
                    header('Location: ?user=' . $foundUser['id']);
                } else {
                    echo '<div class="rmenu"><p>' . _t('Do you really want to delete all reviews about user?') . '<br>' .
                        '<a href="?act=karma&amp;mod=clean&amp;user=' . $foundUser['id'] . '&amp;yes">' . _t('Delete') . '</a> | ' .
                        '<a href="?act=karma&amp;user=' . $foundUser['id'] . '">' . _t('Cancel') . '</a></p></div>';
                }
            }

            break;

        case 'new':
            // Список новых отзывов (комментариев)
            echo '<div class="phdr"><a href="?act=karma&amp;type=2"><b>' . _t('Karma') . '</b></a> | ' . _t('New responses') . '</div>';
            $total = $db->query("SELECT COUNT(*) FROM `karma_users` WHERE `karma_user` = '" . $user->id . "' AND `time` > " . (time() - 86400))->fetchColumn();

            if ($total) {
                $req = $db->query("SELECT * FROM `karma_users` WHERE `karma_user` = '" . $user->id . "' AND `time` > " . (time() - 86400) . " ORDER BY `time` DESC LIMIT ${start}, ${kmess}");

                while ($res = $req->fetch()) {
                    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                    echo $res['type'] ? '<span class="green">+' . $res['points'] . '</span> ' : '<span class="red">-' . $res['points'] . '</span> ';
                    echo $user->id == $res['user_id'] || ! $res['user_id'] ? '<b>' . $res['name'] . '</b>' : '<a href="?user=' . $res['user_id'] . '"><b>' . $res['name'] . '</b></a>';
                    echo ' <span class="gray">(' . $tools->displayDate($res['time']) . ')</span>';
                    if (! empty($res['text'])) {
                        echo '<div class="sub">' . $tools->checkout($res['text']) . '</div>';
                    }
                    echo '</div>';
                    ++$i;
                }
            } else {
                echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
            }
            echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

            if ($total > $kmess) {
                echo '<p>' . $tools->displayPagination('?act=karma&amp;mod=new&amp;', $start, $total, $kmess) . '</p>' .
                    '<p><form action="?act=karma&amp;mod=new" method="post">' .
                    '<input type="text" name="page" size="2"/>' .
                    '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
            }

            echo '<p><a href="./">' . _t('Profile') . '</a></p>';
            break;

        default:
            // Главная страница Кармы, список отзывов
            $type = isset($_GET['type']) ? abs((int) ($_GET['type'])) : 0;
            $menu = [
                ($type == 2 ? '<b>' . _t('All') . '</b>' : '<a href="?act=karma&amp;user=' . $foundUser['id'] . '&amp;type=2">' . _t('All') . '</a>'),
                ($type == 1 ? '<b>' . _t('Positive') . '</b>' : '<a href="?act=karma&amp;user=' . $foundUser['id'] . '&amp;type=1">' . _t('Positive') . '</a>'),
                (! $type ? '<b>' . _t('Negative') . '</b>' : '<a href="?act=karma&amp;user=' . $foundUser['id'] . '">' . _t('Negative') . '</a>'),
            ];
            echo '<div class="phdr"><a href="?user=' . $foundUser['id'] . '"><b>' . _t('Profile') . '</b></a> | ' . _t('Karma') . '</div>' .
                '<div class="topmenu">' . implode(' | ', $menu) . '</div>' .
                '<div class="user"><p>' . $tools->displayUser($foundUser, ['iphide' => 1]) . '</p></div>';
            $karma = $foundUser['karma_plus'] - $foundUser['karma_minus'];

            if ($karma > 0) {
                $images = ($foundUser['karma_minus'] ? ceil($foundUser['karma_plus'] / $foundUser['karma_minus']) : $foundUser['karma_plus']) > 10 ? '2' : '1';
                echo '<div class="gmenu">';
            } else {
                if ($karma < 0) {
                    $images = ($foundUser['karma_plus'] ? ceil($foundUser['karma_minus'] / $foundUser['karma_plus']) : $foundUser['karma_minus']) > 10 ? '-2' : '-1';
                    echo '<div class="rmenu">';
                } else {
                    $images = 0;
                    echo '<div class="menu">';
                }
            }

            echo '<table  width="100%"><tr><td width="22" valign="top"><img src="' . $assets->url('images/old/k_' . $images . '.gif') . '"/></td><td>' .
                '<b>' . _t('Karma') . ' (' . $karma . ')</b>' .
                '<div class="sub">' .
                '<span class="green">' . _t('For') . ' (' . $foundUser['karma_plus'] . ')</span> | ' .
                '<span class="red">' . _t('Against') . ' (' . $foundUser['karma_minus'] . ')</span>';
            echo '</div></td></tr></table></div>';
            $total = $db->query("SELECT COUNT(*) FROM `karma_users` WHERE `karma_user` = '" . $foundUser['id'] . "'" . ($type == 2 ? '' : " AND `type` = '${type}'"))->fetchColumn();

            if ($total) {
                $req = $db->query("SELECT * FROM `karma_users` WHERE `karma_user` = '" . $foundUser['id'] . "'" . ($type == 2 ? '' : " AND `type` = '${type}'") . " ORDER BY `time` DESC LIMIT ${start}, ${kmess}");
                $i = 0;

                while ($res = $req->fetch()) {
                    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                    echo $res['type'] ? '<span class="green">+' . $res['points'] . '</span> ' : '<span class="red">-' . $res['points'] . '</span> ';
                    echo $user->id == $res['user_id'] || ! $res['user_id'] ? '<b>' . $res['name'] . '</b>' : '<a href="?user=' . $res['user_id'] . '"><b>' . $res['name'] . '</b></a>';
                    echo ' <span class="gray">(' . $tools->displayDate($res['time']) . ')</span>';

                    if ($user->rights == 9) {
                        echo ' <span class="red"><a href="?act=karma&amp;mod=delete&amp;user=' . $foundUser['id'] . '&amp;id=' . $res['id'] . '&amp;type=' . $type . '">[X]</a></span>';
                    }

                    if (! empty($res['text'])) {
                        echo '<br />' . $tools->smilies($tools->checkout($res['text']));
                    }

                    echo '</div>';
                    ++$i;
                }
            } else {
                echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
            }

            echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

            if ($total > $kmess) {
                echo '<div class="topmenu">' . $tools->displayPagination('?act=karma&amp;user=' . $foundUser['id'] . '&amp;type=' . $type . '&amp;', $start, $total, $kmess) . '</div>' .
                    '<p><form action="?act=karma&amp;user=' . $foundUser['id'] . '&amp;type=' . $type . '" method="post">' .
                    '<input type="text" name="page" size="2"/>' .
                    '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
            }

            echo '<p>' . ($user->rights == 9 ? '<a href="?act=karma&amp;user=' . $foundUser['id'] . '&amp;mod=clean">' . _t('Reset Karma') . '</a><br />' : '') .
                '<a href="?user=' . $foundUser['id'] . '">' . _t('Profile') . '</a></p>';
    }
}
