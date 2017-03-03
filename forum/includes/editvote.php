<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

if ($systemUser->rights == 3 || $systemUser->rights >= 6) {
    $topic_vote = $db->query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type`='1' AND `topic`='$id'")->fetchColumn();
    require('../system/head.php');

    if ($topic_vote == 0) {
        echo $tools->displayError(_t('Wrong data'));
        require('../system/end.php');
        exit;
    }

    if (isset($_GET['delvote']) && !empty($_GET['vote'])) {
        $vote = abs(intval($_GET['vote']));
        $totalvote = $db->query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type` = '2' AND `id` = '$vote' AND `topic` = '$id'")->fetchColumn();
        $countvote = $db->query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type` = '2' AND `topic` = '$id'")->fetchColumn();

        if ($countvote <= 2) {
            header('location: ?act=editvote&id=' . $id . '');
        }

        if ($totalvote != 0) {
            if (isset($_GET['yes'])) {
                $db->exec("DELETE FROM `cms_forum_vote` WHERE `id` = '$vote'");
                $countus = $db->query("SELECT COUNT(*) FROM `cms_forum_vote_users` WHERE `vote` = '$vote' AND `topic` = '$id'")->fetchColumn();
                $topic_vote = $db->query("SELECT `count` FROM `cms_forum_vote` WHERE `type` = '1' AND `topic` = '$id' LIMIT 1")->fetch();
                $totalcount = $topic_vote['count'] - $countus;
                $db->exec("UPDATE `cms_forum_vote` SET  `count` = '$totalcount'   WHERE `type` = '1' AND `topic` = '$id'");
                $db->exec("DELETE FROM `cms_forum_vote_users` WHERE `vote` = '$vote'");
                header('location: ?act=editvote&id=' . $id . '');
            } else {
                echo '<div class="rmenu"><p>' . _t('Do you really want to delete the answer?') . '<br />' .
                    '<a href="index.php?act=editvote&amp;id=' . $id . '&amp;vote=' . $vote . '&amp;delvote&amp;yes">' . _t('Delete') . '</a><br />' .
                    '<a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">' . _t('Cancel') . '</a></p></div>';
            }
        } else {
            header('location: ?act=editvote&id=' . $id . '');
        }
    } else {
        if (isset($_POST['submit'])) {
            $vote_name = mb_substr(trim($_POST['name_vote']), 0, 50);

            if (!empty($vote_name)) {
                $db->exec("UPDATE `cms_forum_vote` SET  `name` = " . $db->quote($vote_name) . "  WHERE `topic` = '$id' AND `type` = '1'");
            }

            $vote_result = $db->query("SELECT `id` FROM `cms_forum_vote` WHERE `type`='2' AND `topic`='" . $id . "'");

            while ($vote = $vote_result->fetch()) {
                if (!empty($_POST[$vote['id'] . 'vote'])) {
                    $text = mb_substr(trim($_POST[$vote['id'] . 'vote']), 0, 30);
                    $db->exec("UPDATE `cms_forum_vote` SET  `name` = " . $db->quote($text) . "  WHERE `id` = '" . $vote['id'] . "'");
                }
            }

            $countvote = $db->query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type`='2' AND `topic`='" . $id . "'")->fetchColumn();

            for ($vote = $countvote; $vote < 20; $vote++) {
                if (!empty($_POST[$vote])) {
                    $text = mb_substr(trim($_POST[$vote]), 0, 30);
                    $db->exec("INSERT INTO `cms_forum_vote` SET `name` = " . $db->quote($text) . ",  `type` = '2', `topic` = '$id'");
                }
            }

            echo '<div class="gmenu"><p>' . _t('Poll changed') . '<br /><a href="index.php?id=' . $id . '">' . _t('Continue') . '</a></p></div>';
        } else {
            // Форма редактирования опроса
            $countvote = $db->query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type` = '2' AND `topic` = '$id'")->fetchColumn();
            $topic_vote = $db->query("SELECT `name` FROM `cms_forum_vote` WHERE `type` = '1' AND `topic` = '$id' LIMIT 1")->fetch();
            echo '<div class="phdr"><a href="index.php?id=' . $id . '"><b>' . _t('Forum') . '</b></a> | ' . _t('Edit Poll') . '</div>' .
                '<form action="index.php?act=editvote&amp;id=' . $id . '" method="post">' .
                '<div class="gmenu"><p>' .
                '<b>' . _t('Poll (max. 150)') . ':</b><br>' .
                '<input type="text" size="20" maxlength="150" name="name_vote" value="' . htmlentities($topic_vote['name'], ENT_QUOTES, 'UTF-8') . '"/>' .
                '</p></div>' .
                '<div class="menu"><p>';
            $vote_result = $db->query("SELECT `id`, `name` FROM `cms_forum_vote` WHERE `type` = '2' AND `topic` = '$id'");

            while ($vote = $vote_result->fetch()) {
                echo _t('Answer') . ' ' . ($i + 1) . ' (max. 50): <br>' .
                    '<input type="text" name="' . $vote['id'] . 'vote" value="' . htmlentities($vote['name'], ENT_QUOTES, 'UTF-8') . '"/>';

                if ($countvote > 2) {
                    echo '&nbsp;<a href="index.php?act=editvote&amp;id=' . $id . '&amp;vote=' . $vote['id'] . '&amp;delvote">[x]</a>';
                }

                echo '<br>';
                ++$i;
            }

            if ($countvote < 20) {
                if (isset($_POST['plus'])) {
                    ++$_POST['count_vote'];
                } elseif (isset($_POST['minus'])) {
                    --$_POST['count_vote'];
                }

                if (empty($_POST['count_vote'])) {
                    $_POST['count_vote'] = $countvote;
                } elseif ($_POST['count_vote'] > 20) {
                    $_POST['count_vote'] = 20;
                }

                for ($vote = $i; $vote < $_POST['count_vote']; $vote++) {
                    echo 'Ответ ' . ($vote + 1) . '(max. 50): <br><input type="text" name="' . $vote . '" value="' . $tools->checkout($_POST[$vote]) . '"/><br>';
                }

                echo '<input type="hidden" name="count_vote" value="' . abs(intval($_POST['count_vote'])) . '"/>' . ($_POST['count_vote'] < 20 ? '<input type="submit" name="plus" value="' . _t('Add') . '"/>' : '')
                    . ($_POST['count_vote'] - $countvote ? '<input type="submit" name="minus" value="' . _t('Delete last') . '"/>' : '');
            }
            echo '</p></div><div class="gmenu">' .
                '<p><input type="submit" name="submit" value="' . _t('Save') . '"/></p>' .
                '</div></form>' .
                '<div class="phdr"><a href="index.php?id=' . $id . '">' . _t('Cancel') . '</a></div>';
        }
    }
}
