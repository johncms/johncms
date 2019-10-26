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

require 'system/head.php';

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

if (! $id) {
    echo $tools->displayError(_t('Wrong data'), '<a href="./">' . _t('Forum') . '</a>');
    require 'system/end.php';
    exit;
}

switch ($do) {
    case 'unset':
        // Удаляем фильтр
        unset($_SESSION['fsort_id'], $_SESSION['fsort_users']);

        header("Location: ?type=topic&id=${id}");
        break;

    case 'set':
        // Устанавливаем фильтр по авторам
        $users = $_POST['users'] ?? '';

        if (empty($_POST['users'])) {
            echo '<div class="rmenu"><p>' . _t('You have not selected any author') . '<br /><a href="?type=topic&act=filter&amp;id=' . $id . '&amp;start=' . $start . '">' . _t('Back') . '</a></p></div>';
            require 'system/end.php';
            exit;
        }

        $array = [];

        foreach ($users as $val) {
            $array[] = (int) $val;
        }

        $_SESSION['fsort_id'] = $id;
        $_SESSION['fsort_users'] = serialize($array);
        header("Location: ?type=topic&id=${id}");
        break;

    default:
        /** @var PDO $db */
        $db = $container->get(PDO::class);

        // Показываем список авторов темы, с возможностью выбора
        $req = $db->query("SELECT *, COUNT(`user_id`) AS `count` FROM `forum_messages` WHERE `topic_id` = '${id}' GROUP BY `user_id` ORDER BY `user_name`");
        $total = $req->rowCount();

        if ($total) {
            echo '<div class="phdr"><a href="?type=topic&id=' . $id . '&amp;start=' . $start . '"><b>' . _t('Forum') . '</b></a> | ' . _t('Filter by author') . '</div>' .
                '<form action="?act=filter&amp;id=' . $id . '&amp;start=' . $start . '&amp;do=set" method="post">';
            $i = 0;

            while ($res = $req->fetch()) {
                echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                echo '<input type="checkbox" name="users[]" value="' . $res['user_id'] . '"/>&#160;' .
                    '<a href="../profile/?user=' . $res['user_id'] . '">' . $res['user_name'] . '</a> [' . $res['count'] . ']</div>';
                ++$i;
            }

            echo '<div class="gmenu"><input type="submit" value="' . _t('Filter') . '" name="submit" /></div>' .
                '<div class="phdr"><small>' . _t('Filter will be display posts from selected authors only') . '</small></div>' .
                '</form>';
        } else {
            echo $tools->displayError(_t('Wrong data'));
        }
}

echo '<p><a href="?id=' . $id . '&amp;start=' . $start . '">' . _t('Back to topic') . '</a></p>';
