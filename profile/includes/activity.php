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

// История активности
$textl = htmlspecialchars($user['name']) . ': ' . _t('Activity');
require('../system/head.php');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

echo '<div class="phdr"><a href="?user=' . $user['id'] . '"><b>' . _t('Profile') . '</b></a> | ' . _t('Activity') . '</div>';
$menu = [
    (!$mod ? '<b>' . _t('Messages') . '</b>' : '<a href="?act=activity&amp;user=' . $user['id'] . '">' . _t('Messages') . '</a>'),
    ($mod == 'topic' ? '<b>' . _t('Themes') . '</b>' : '<a href="?act=activity&amp;mod=topic&amp;user=' . $user['id'] . '">' . _t('Themes') . '</a>'),
    ($mod == 'comments' ? '<b>' . _t('Comments') . '</b>' : '<a href="?act=activity&amp;mod=comments&amp;user=' . $user['id'] . '">' . _t('Comments') . '</a>'),
];
echo '<div class="topmenu">' . implode(' | ', $menu) . '</div>' .
    '<div class="user"><p>' . $tools->displayUser($user, ['iphide' => 1,]) . '</p></div>';

switch ($mod) {
    case 'comments':
        // Список сообщений в Гостевой
        $total = $db->query("SELECT COUNT(*) FROM `guest` WHERE `user_id` = '" . $user['id'] . "'" . ($systemUser->rights >= 1 ? '' : " AND `adm` = '0'"))->fetchColumn();
        echo '<div class="phdr"><b>' . _t('Comments') . '</b></div>';

        if ($total > $kmess) {
            echo '<div class="topmenu">' . $tools->displayPagination('?act=activity&amp;mod=comments&amp;user=' . $user['id'] . '&amp;', $start, $total, $kmess) . '</div>';
        }

        $req = $db->query("SELECT * FROM `guest` WHERE `user_id` = '" . $user['id'] . "'" . ($systemUser->rights >= 1 ? '' : " AND `adm` = '0'") . " ORDER BY `id` DESC LIMIT $start, $kmess");

        if ($req->rowCount()) {
            $i = 0;
            while ($res = $req->fetch()) {
                echo ($i % 2 ? '<div class="list2">' : '<div class="list1">') . $tools->checkout($res['text'], 2, 1) . '<div class="sub">' .
                    '<span class="gray">(' . $tools->displayDate($res['time']) . ')</span>' .
                    '</div></div>';
                ++$i;
            }
        } else {
            echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
        }
        break;

    case 'topic':
        // Список тем Форума
        $total = $db->query("SELECT COUNT(*) FROM `forum` WHERE `user_id` = '" . $user['id'] . "' AND `type` = 't'" . ($systemUser->rights >= 7 ? '' : " AND `close`!='1'"))->fetchColumn();
        echo '<div class="phdr"><b>' . _t('Forum') . '</b>: ' . _t('Themes') . '</div>';

        if ($total > $kmess) {
            echo '<div class="topmenu">' . $tools->displayPagination('?act=activity&amp;mod=topic&amp;user=' . $user['id'] . '&amp;', $start, $total, $kmess) . '</div>';
        }

        $req = $db->query("SELECT * FROM `forum` WHERE `user_id` = '" . $user['id'] . "' AND `type` = 't'" . ($systemUser->rights >= 7 ? '' : " AND `close`!='1'") . " ORDER BY `id` DESC LIMIT $start, $kmess");

        if ($req->rowCount()) {
            $i = 0;

            while ($res = $req->fetch()) {
                $post = $db->query("SELECT * FROM `forum` WHERE `refid` = '" . $res['id'] . "'" . ($systemUser->rights >= 7 ? '' : " AND `close`!='1'") . " ORDER BY `id` ASC LIMIT 1")->fetch();
                $section = $db->query("SELECT * FROM `forum` WHERE `id` = '" . $res['refid'] . "'")->fetch();
                $category = $db->query("SELECT * FROM `forum` WHERE `id` = '" . $section['refid'] . "'")->fetch();
                $text = mb_substr($post['text'], 0, 300);
                $text = $tools->checkout($text, 2, 1);
                echo ($i % 2 ? '<div class="list2">' : '<div class="list1">') .
                    '<a href="' . $config->homeurl . '/forum/index.php?id=' . $res['id'] . '">' . $res['text'] . '</a>' .
                    '<br />' . $text . '...<a href="' . $config->homeurl . '/forum/index.php?id=' . $res['id'] . '"> &gt;&gt;</a>' .
                    '<div class="sub">' .
                    '<a href="' . $config->homeurl . '/forum/index.php?id=' . $category['id'] . '">' . $category['text'] . '</a> | ' .
                    '<a href="' . $config->homeurl . '/forum/index.php?id=' . $section['id'] . '">' . $section['text'] . '</a>' .
                    '<br /><span class="gray">(' . $tools->displayDate($res['time']) . ')</span>' .
                    '</div></div>';
                ++$i;
            }
        } else {
            echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
        }
        break;

    default:
        // Список постов Форума
        $total = $db->query("SELECT COUNT(*) FROM `forum` WHERE `user_id` = '" . $user['id'] . "' AND `type` = 'm'" . ($systemUser->rights >= 7 ? '' : " AND `close`!='1'"))->fetchColumn();
        echo '<div class="phdr"><b>' . _t('Forum') . '</b>: ' . _t('Messages') . '</div>';

        if ($total > $kmess) {
            echo '<div class="topmenu">' . $tools->displayPagination('?act=activity&amp;user=' . $user['id'] . '&amp;', $start, $total, $kmess) . '</div>';
        }

        $req = $db->query("SELECT * FROM `forum` WHERE `user_id` = '" . $user['id'] . "' AND `type` = 'm' " . ($systemUser->rights >= 7 ? '' : " AND `close`!='1'") . " ORDER BY `id` DESC LIMIT $start, $kmess");

        if ($req->rowCount()) {
            $i = 0;

            while ($res = $req->fetch()) {
                $topic = $db->query("SELECT * FROM `forum` WHERE `id` = '" . $res['refid'] . "'")->fetch();
                $section = $db->query("SELECT * FROM `forum` WHERE `id` = '" . $topic['refid'] . "'")->fetch();
                $category = $db->query("SELECT * FROM `forum` WHERE `id` = '" . $section['refid'] . "'")->fetch();
                $text = mb_substr($res['text'], 0, 300);
                $text = $tools->checkout($text, 2, 1);
                $text = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $text);

                echo ($i % 2 ? '<div class="list2">' : '<div class="list1">') .
                    '<a href="' . $config->homeurl . '/forum/index.php?id=' . $topic['id'] . '">' . $topic['text'] . '</a>' .
                    '<br />' . $text . '...<a href="' . $config->homeurl . '/forum/index.php?act=post&amp;id=' . $res['id'] . '"> &gt;&gt;</a>' .
                    '<div class="sub">' .
                    '<a href="' . $config->homeurl . '/forum/index.php?id=' . $category['id'] . '">' . $category['text'] . '</a> | ' .
                    '<a href="' . $config->homeurl . '/forum/index.php?id=' . $section['id'] . '">' . $section['text'] . '</a>' .
                    '<br /><span class="gray">(' . $tools->displayDate($res['time']) . ')</span>' .
                    '</div></div>';
                ++$i;
            }
        } else {
            echo '<div class="menu"><p>' . _t('The list is empty') . '</p></div>';
        }
}

echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

if ($total > $kmess) {
    echo '<div class="topmenu">' . $tools->displayPagination('?act=activity' . ($mod ? '&amp;mod=' . $mod : '') . '&amp;user=' . $user['id'] . '&amp;', $start, $total, $kmess) . '</div>' .
        '<p><form action="?act=activity&amp;user=' . $user['id'] . ($mod ? '&amp;mod=' . $mod : '') . '" method="post">' .
        '<input type="text" name="page" size="2"/>' .
        '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/>' .
        '</form></p>';
}
