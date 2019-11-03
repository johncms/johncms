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

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $user */
$user = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

if ($user->rights == 3 || $user->rights >= 6) {
    $topic = $db->query("SELECT COUNT(*) FROM `forum_topic` WHERE `id`='${id}' AND (`deleted` != '1' OR `deleted` IS NULL)")->fetchColumn();
    $topic_vote = $db->query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type`='1' AND `topic`='${id}'")->fetchColumn();

    if ($topic_vote != 0 || $topic == 0) {
        echo $tools->displayError(_t('Wrong data'), '<a href="' . htmlspecialchars(getenv('HTTP_REFERER')) . '">' . _t('Back') . '</a>');
        echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
        exit;
    }

    if (isset($_POST['submit'])) {
        $vote_name = mb_substr(trim($_POST['name_vote']), 0, 50);

        if (! empty($vote_name) && ! empty($_POST[0]) && ! empty($_POST[1]) && ! empty($_POST['count_vote'])) {
            $db->exec('INSERT INTO `cms_forum_vote` SET
                `name`=' . $db->quote($vote_name) . ",
                `time`='" . time() . "',
                `type` = '1',
                `topic`='${id}'
            ");
            $db->exec("UPDATE `forum_topic` SET `has_poll` = '1'  WHERE `id` = '${id}'");
            $vote_count = abs((int) ($_POST['count_vote']));

            if ($vote_count > 20) {
                $vote_count = 20;
            } else {
                if ($vote_count < 2) {
                    $vote_count = 2;
                }
            }

            for ($vote = 0; $vote < $vote_count; $vote++) {
                $text = mb_substr(trim($_POST[$vote]), 0, 30);

                if (empty($text)) {
                    continue;
                }

                $db->exec('INSERT INTO `cms_forum_vote` SET
                    `name`=' . $db->quote($text) . ",
                    `type` = '2',
                    `topic`='${id}'
                ");
            }
            echo _t('Poll added') . '<br /><a href="?type=topic&amp;id=' . $id . '">' . _t('Continue') . '</a>';
        } else {
            echo _t('The required fields are not filled') . '<br /><a href="?act=addvote&amp;id=' . $id . '">' . _t('Repeat') . '</a>';
        }
    } else {
        echo '<form action="?act=addvote&amp;id=' . $id . '" method="post">' .
            '<br />' . _t('Poll (max. 150)') . ':<br>' .
            '<input type="text" size="20" maxlength="150" name="name_vote" value="' . htmlentities($_POST['name_vote'], ENT_QUOTES, 'UTF-8') . '"/><br>';

        if (isset($_POST['plus'])) {
            ++$_POST['count_vote'];
        } elseif (isset($_POST['minus'])) {
            --$_POST['count_vote'];
        }

        if ($_POST['count_vote'] < 2 || empty($_POST['count_vote'])) {
            $_POST['count_vote'] = 2;
        } elseif ($_POST['count_vote'] > 20) {
            $_POST['count_vote'] = 20;
        }

        for ($vote = 0; $vote < $_POST['count_vote']; $vote++) {
            echo _t('Answer') . ' ' . ($vote + 1) . '(max. 50): <br><input type="text" name="' . $vote . '" value="' . htmlentities($_POST[$vote], ENT_QUOTES, 'UTF-8') . '"/><br>';
        }

        echo '<input type="hidden" name="count_vote" value="' . abs((int) ($_POST['count_vote'])) . '"/>';
        echo ($_POST['count_vote'] < 20) ? '<br><input type="submit" name="plus" value="' . _t('Add Answer') . '"/>' : '';
        echo $_POST['count_vote'] > 2 ? '<input type="submit" name="minus" value="' . _t('Delete last') . '"/><br>' : '<br>';
        echo '<p><input type="submit" name="submit" value="' . _t('Save') . '"/></p></form>';
        echo '<a href="?type=topic&amp;id=' . $id . '">' . _t('Back') . '</a>';
    }
}
