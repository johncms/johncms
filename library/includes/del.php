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

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

use Library\Tree;
use Library\Utils;

if (!$adm) {
    Utils::redir404();
}

echo '<div class="phdr"><strong><a href="?">' . _t('Library') . '</a></strong> | ' . _t('Delete') . '</div>';

if (isset($_GET['type']) && in_array($_GET['type'], ['dir', 'article', 'image'])) {
    $type = $_GET['type'];
} else {
    Utils::redir404();
}
$change = ($type == 'dir' ? $db->query("SELECT COUNT(*) FROM `library_cats` WHERE `parent`=" . $id)->fetchColumn() > 0 || $db->query("SELECT COUNT(*) FROM `library_texts` WHERE `cat_id`=" . $id)->fetchColumn() > 0 ? 0 : 1 : '');

switch ($type) {
    case 'dir':
        if ($db->query("SELECT COUNT(*) FROM `library_cats` WHERE `id`=" . $id)->fetchColumn() == 0) {
            echo $tools->displayError(_t('Section does not exist'));
        } elseif (!$change) {
            $mode = isset($_POST['mode']) ? $_POST['mode'] : (isset($do) ? $do : false);
            $dirtype = $db->query("SELECT `dir` FROM `library_cats` WHERE `id` = " . $id . " LIMIT 1")->fetchColumn();
            switch ($mode) {
                case 'moveaction':
                    if (!isset($_GET['movedeny'])) {
                        echo '<div class="alarm"><div>' . _t('Are you sure you want to move the contents?') . '</div><div><a href="?act=del&amp;type=' . $type . '&amp;id=' . $id . '&amp;movedeny&amp;do=moveaction&amp;move=' . intval($_POST['move']) . '">' . _t('Move') . '</a> | <a href="?">' . _t('Cancel') . '</a></div></div>';
                    } else {
                        $move = intval($_GET['move']);
                        if ($dirtype) {
                            $afr = $db->exec("UPDATE `library_cats` SET `parent`=" . $move . " WHERE `parent` = " . $id);
                        } else {
                            $afr = $db->exec("UPDATE `library_texts` SET `cat_id` = " . $move . " WHERE `cat_id` = " . $id);
                        }

                        if ($afr) {
                            $afr = $db->exec("DELETE FROM `library_cats` WHERE `id` = " . $id);
                            if ($afr) {
                                echo '<div class="gmenu">' . _t('Successful transfer') . '</div><div><a href="?do=dir&amp;id=' . $move . '">' . _t('Back') . '</a></div>' . PHP_EOL;
                            }
                        }
                    }
                    break;

                case 'delmove':
                    $child_dir = new Tree($id);
                    $childrens = $child_dir->getChildsDir()->result();
                    $list = $db->query("SELECT `id`, `name` FROM `library_cats` WHERE `dir`=" . $dirtype . " AND " . ($dirtype && sizeof($childrens) ? '`id` NOT IN(' . implode(', ', $childrens) . ', ' . $id . ')' : '`id`  != ' . $id));
                    if ($list->rowCount()) {
                        echo '<div class="menu">'
                            . '<h3>' . _t('Move to Section') . '</h3>'
                            . '<form action="?act=del&amp;type=dir&amp;id=' . $id . '" method="post">'
                            . '<div><select name="move">';
                        while ($rm = $list->fetch()) {
                            echo '<option value="' . $rm['id'] . '">' . $tools->checkout($rm['name']) . '</option>';
                        }
                        echo '</select></div>'
                            . '<div><input type="hidden" name="mode" value="moveaction" /></div>'
                            . '<div class="bmenu"><input type="submit" name="submit" value="' . _t('Approve') . '" /></div>'
                            . '</form>'
                            . '</div>';
                    } else {
                        echo '<div class="rmenu">' . _t('There are no Sections for moving') . '</div><div class="bmenu"><a href="?">' . _t('Back') . '</a></div>';
                    }
                    break;

                case 'delall':
                    if (!isset($_GET['deldeny'])) {
                        echo '<div class="alarm"><div>' . _t('Are you sure you want to delete content?') . '</div><div><a href="?act=del&amp;type=' . $type . '&amp;id=' . $id . '&amp;deldeny&amp;do=delall">' . _t('Delete') . '</a> | <a href="?">' . _t('Cancel') . '</a></div></div>';
                    } else {
                        $childs = new Tree($id);
                        $deleted = $childs->getAllChildsId()->cleanDir();
                        echo '<div class="gmenu">' . sprintf(_t('Successfully deleted:<br>Directories: (%d)<br>Articles: (%d)<br>Tags: (%d)<br>Comments: (%d)<br>Images: (%d)'),
                                $deleted['dirs'], $deleted['texts'], $deleted['tags'],
                                $deleted['comments'],
                                $deleted['images']) . '</div><div><a href="?">' . _t('Back') . '</a></div>' . PHP_EOL;
                    }
                    break;

                default:
                    echo '<div class="alarm">' . _t('Section is not empty') . '</div>'
                        . '<div class="menu"><h3>' . _t('Select action') . '</h3>'
                        . '<form action="?act=del&amp;type=dir&amp;id=' . $id . '" method="post">'
                        . '<div><input type="radio" name="mode" value="delmove" checked="checked" /> ' . _t('Delete with movement') . '</div>'
                        . '<div><input type="radio" name="mode" value="delall" /> <span style="color: red;"> ' . _t('Delete all Sections and Articles') . '</span></div>'
                        . '<div class="bmenu"><input type="submit" name="submit" value="' . _t('Do') . '" /></div>'
                        . '</form>'
                        . '</div>';
                    break;
            }
        } else {
            $sql = "DELETE FROM `library_cats` WHERE `id`=" . $id;
            if (!isset($_GET['yes'])) {
                echo '<div class="alarm"><div>' . _t('Delete confirmation') . '</div><div><a href="?act=del&amp;type=' . $type . '&amp;id=' . $id . '&amp;yes">' . _t('Delete') . '</a> | <a href="?do=dir&amp;id=' . $id . '">' . _t('Cancel') . '</a></div></div>';
            }
        }
        break;

    case 'article':
        if ($db->query("SELECT COUNT(*) FROM `library_texts` WHERE `id`=" . $id)->rowCount() == 0) {
            echo $tools->displayError(_t('Articles do not exist'));
        } else {
            $sql = "DELETE FROM `library_texts` WHERE `id`=" . $id;
            if (!isset($_GET['yes'])) {
                echo '<div class="alarm"><div>' . _t('Delete confirmation') . '</div><div><a href="?act=del&amp;type=' . $type . '&amp;id=' . $id . '&amp;yes">' . _t('Delete') . '</a> | <a href="index.php?id=' . $id . '">' . _t('Cancel') . '</a></div></div>';
            }
        }
        break;
    case 'image':
        if (!isset($_GET['yes'])) {
            echo '<div class="alarm"><div>' . _t('Delete confirmation') . '</div><div><a href="?act=del&amp;type=' . $type . '&amp;id=' . $id . '&amp;yes">' . _t('Delete') . '</a> | <a href="?act=moder&amp;type=article&amp;id=' . $id . '">' . _t('Cancel') . '</a></div></div>';
        }
        break;
}
if (isset($_GET['yes']) && $type == 'image') {
    if (file_exists('../files/library/images/small/' . $id . '.png')) {
        @unlink('../files/library/images/big/' . $id . '.png');
        @unlink('../files/library/images/orig/' . $id . '.png');
        @unlink('../files/library/images/small/' . $id . '.png');
    }
    echo '<div class="gmenu">' . _t('Deleted') . '</div><div><a href="?act=moder&amp;type=article&amp;id=' . $id . '">' . _t('Back') . '</a></div>' . PHP_EOL;
} elseif (isset($_GET['yes'])) {
    if ($db->exec($sql)) {
        if (file_exists('../files/library/images/small/' . $id . '.png')) {
            @unlink('../files/library/images/big/' . $id . '.png');
            @unlink('../files/library/images/orig/' . $id . '.png');
            @unlink('../files/library/images/small/' . $id . '.png');
        }
        echo '<div class="gmenu">' . _t('Deleted') . '</div><p><a href="?">' . _t('Back') . '</a></p>' . PHP_EOL;
    }
}
