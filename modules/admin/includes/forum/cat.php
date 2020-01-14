<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

defined('_IN_JOHNADM') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 * @var Johncms\NavChain $nav_chain
 * @var Johncms\System\Http\Request $request
 */


// Управление категориями и разделами
echo '<div class="phdr"><a href="?act=forum"><b>' . __('Forum Management') . '</b></a> | ' . __('Forum structure') . '</div>';

if ($id) {
    // Управление разделами
    $req = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '${id}'");
    $res = $req->fetch();
    echo '<div class="bmenu"><a href="?act=forum&amp;mod=cat' . (! empty($res['parent']) ? '&amp;id=' . $res['parent'] : '') . '"><b>' . $res['name'] . '</b></a> | ' . __('List of sections') . '</div>';
    $req = $db->query("SELECT * FROM `forum_sections` WHERE `parent` = '${id}' ORDER BY `sort` ASC");

    if ($req->rowCount()) {
        $i = 0;

        while ($res = $req->fetch()) {
            echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
            echo '[' . $res['sort'] . '] <a href="?act=forum&amp;mod=cat&amp;id=' . $res['id'] . '"><b>' . $res['name'] . '</b></a>' .
                '&#160;<a href="../forum/?id=' . $res['id'] . '">&gt;&gt;</a>';

            if (! empty($res['description'])) {
                echo '<br><span class="gray"><small>' . $res['description'] . '</small></span><br>';
            }

            echo '<div class="sub">' .
                '<a href="?act=forum&amp;mod=edit&amp;id=' . $res['id'] . '">' . __('Edit') . '</a> | ' .
                '<a href="?act=forum&amp;mod=del&amp;id=' . $res['id'] . '">' . __('Delete') . '</a>' .
                '</div></div>';
            ++$i;
        }
    } else {
        echo '<div class="menu"><p>' . __('The list is empty') . '</p></div>';
    }
} else {
    // Управление категориями
    echo '<div class="bmenu">' . __('List of categories') . '</div>';
    $req = $db->query('SELECT * FROM `forum_sections` WHERE `parent` = 0 OR `parent` IS NULL ORDER BY `sort` ASC');
    $i = 0;

    while ($res = $req->fetch()) {
        echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
        echo '[' . $res['sort'] . '] <a href="?act=forum&amp;mod=cat&amp;id=' . $res['id'] . '"><b>' . $res['name'] . '</b></a> ' .
            '(' . $db->query("SELECT COUNT(*) FROM `forum_sections` WHERE `parent` = '" . $res['id'] . "'")->fetchColumn() . ')' .
            '&#160;<a href="../forum/?id=' . $res['id'] . '">&gt;&gt;</a>';

        if (! empty($res['description'])) {
            echo '<br><span class="gray"><small>' . $res['description'] . '</small></span><br>';
        }

        echo '<div class="sub">' .
            '<a href="?act=forum&amp;mod=edit&amp;id=' . $res['id'] . '">' . __('Edit') . '</a> | ' .
            '<a href="?act=forum&amp;mod=del&amp;id=' . $res['id'] . '">' . __('Delete') . '</a>' .
            '</div></div>';
        ++$i;
    }
}

echo '<div class="gmenu">' .
    '<form action="?act=forum&amp;mod=add' . ($id ? '&amp;id=' . $id : '') . '" method="post">' .
    '<input type="submit" value="' . __('Add') . '" />' .
    '</form></div>' .
    '<div class="phdr">' . ($mod == 'cat' && $id ? '<a href="?act=forum&amp;mod=cat">' . __('List of categories') . '</a>' : '<a href="?act=forum">' . __('Forum Management') . '</a>') . '</div>';
