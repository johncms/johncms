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
 * @var League\Plates\Engine       $view
 */

// Добавление новости
if ($user->rights >= 6) {
    echo '<div class="phdr"><a href="./"><b>' . _t('News') . '</b></a> | ' . _t('Add') . '</div>';
    $old = 20;

    if (isset($_POST['submit'])) {
        $error = [];
        $name = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name'])) : false;
        $text = isset($_POST['text']) ? trim($_POST['text']) : false;

        if (! $name) {
            $error[] = _t('You have not entered news title');
        }

        if (! $text) {
            $error[] = _t('You have not entered news text');
        }

        $flood = $tools->antiflood();

        if ($flood) {
            $error[] = sprintf(_t('You cannot add the message so often. Please, wait %d seconds.'), $flood);
        }

        if (! $error) {
            $rid = 0;

            if (! empty($_POST['pf']) && ($_POST['pf'] != '0')) {
                $pf = (int) ($_POST['pf']);
                $rz = $_POST['rz'];
                $pr = $db->query("SELECT * FROM `forum_sections` WHERE `parent` = '${pf}'");

                while ($pr1 = $pr->fetch()) {
                    $arr[] = $pr1['id'];
                }

                foreach ($rz as $v) {
                    if (in_array($v, $arr)) {
                        $date = new DateTime();
                        $date = $date->format('Y-m-d H:i:s');

                        $db->prepare('
                                  INSERT INTO `forum_topic` SET
                                  `section_id` = ?,
                                  `created_at` = ?,
                                  `user_id` = ?,
                                  `user_name` = ?,
                                  `name` = ?,
                                  `last_post_date` = ?,
                                  `post_count` = 0
                                ')->execute([
                            $v,
                            $date,
                            $user->id,
                            $user->name,
                            $name,
                            time(),
                        ]);

                        /** @var Johncms\Api\EnvironmentInterface $env */
                        $env = $container->get(Johncms\Api\EnvironmentInterface::class);
                        $rid = $db->lastInsertId();

                        $db->prepare('
                                  INSERT INTO `forum_messages` SET
                                  `topic_id` = ?,
                                  `date` = ?,
                                  `user_id` = ?,
                                  `user_name` = ?,
                                  `ip` = ?,
                                  `ip_via_proxy` = ?,
                                  `user_agent` = ?,
                                  `text` = ?
                                ')->execute([
                            $rid,
                            time(),
                            $user->id,
                            $user->name,
                            $env->getIp(),
                            $env->getIpViaProxy(),
                            $env->getUserAgent(),
                            $text,
                        ]);
                        $tools->recountForumTopic($rid);
                    }
                }
            }

            $db->prepare('
                      INSERT INTO `news` SET
                      `time` = ?,
                      `avt` = ?,
                      `name` = ?,
                      `text` = ?,
                      `kom` = ?
                    ')->execute([
                time(),
                $user->name,
                $name,
                $text,
                $rid,
            ]);

            $db->exec('UPDATE `users` SET `lastpost` = ' . time() . ' WHERE `id` = ' . $user->id);
            echo '<p>' . _t('News added') . '<br /><a href="./">' . _t('Back to news') . '</a></p>';
        } else {
            echo $tools->displayError($error, '<a href="./">' . _t('Back to news') . '</a>');
        }
    } else {
        echo '<form action="?do=add" method="post"><div class="menu">' .
            '<p><h3>' . _t('Title') . '</h3>' .
            '<input type="text" name="name"/></p>' .
            '<p><h3>' . _t('Text') . '</h3>' .
            '<textarea rows="' . $user->config->fieldHeight . '" name="text"></textarea></p>' .
            '<p><h3>' . _t('Discussion') . '</h3>';
        $fr = $db->query('SELECT * FROM `forum_sections` WHERE `section_type` = 0');
        echo '<input type="radio" name="pf" value="0" checked="checked" />' . _t('Do not discuss') . '<br />';

        while ($fr1 = $fr->fetch()) {
            echo '<input type="radio" name="pf" value="' . $fr1['id'] . '"/>' . $fr1['name'] . '<select name="rz[]">';
            $pr = $db->query("SELECT * FROM `forum_sections` WHERE `section_type` = 1 AND `parent` = '" . $fr1['id'] . "'");

            while ($pr1 = $pr->fetch()) {
                echo '<option value="' . $pr1['id'] . '">' . $pr1['name'] . '</option>';
            }
            echo '</select><br>';
        }

        echo '</p></div><div class="bmenu">' .
            '<input type="submit" name="submit" value="' . _t('Save') . '"/>' .
            '</div></form>' .
            '<p><a href="./">' . _t('Back to news') . '</a></p>';

        echo $view->render('system::app/old_content', [
            'title'   => _t('News'),
            'content' => ob_get_clean(),
        ]);
    }
} else {
    pageNotFound();
}
