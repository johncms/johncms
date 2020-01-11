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

use Library\Tree;
use Library\Utils;

if (! $adm) {
    Utils::redir404();
}

echo '<div class="phdr"><strong><a href="?">' . __('Library') . '</a></strong> | ' . __('Delete') . '</div>';

if (isset($_GET['type']) && in_array($_GET['type'], ['dir', 'article', 'image'])) {
    $type = $_GET['type'];
} else {
    Utils::redir404();
}
$change = ($type == 'dir' ? $db->query('SELECT COUNT(*) FROM `library_cats` WHERE `parent`=' . $id)->fetchColumn() > 0 || $db->query('SELECT COUNT(*) FROM `library_texts` WHERE `cat_id`=' . $id)->fetchColumn() > 0 ? 0 : 1 : '');

switch ($type) {
    case 'dir':
        if ($db->query('SELECT COUNT(*) FROM `library_cats` WHERE `id`=' . $id)->fetchColumn() == 0) {
            echo $tools->displayError(__('Section does not exist'));
        } elseif (! $change) {
            $mode = $_POST['mode'] ?? ($do ?? false);
            $dirtype = $db->query('SELECT `dir` FROM `library_cats` WHERE `id` = ' . $id . ' LIMIT 1')->fetchColumn();
            switch ($mode) {
                case 'moveaction':
                    if (! isset($_GET['movedeny'])) {
                        echo '<div class="alarm"><div>' . __('Are you sure you want to move the contents?') .
                            '</div><div><a href="?act=del&amp;type=' . $type . '&amp;id=' . $id . '&amp;movedeny&amp;do=moveaction&amp;move=' .
                            (int) ($_POST['move']) . '">' . __('Move') . '</a> | <a href="?">' . __('Cancel') . '</a></div></div>';
                    } else {
                        $move = (int) ($_GET['move']);
                        if ($dirtype) {
                            $afr = $db->exec('UPDATE `library_cats` SET `parent`=' . $move . ' WHERE `parent` = ' . $id);
                        } else {
                            $afr = $db->exec('UPDATE `library_texts` SET `cat_id` = ' . $move . ' WHERE `cat_id` = ' . $id);
                        }

                        if ($afr) {
                            $afr = $db->exec('DELETE FROM `library_cats` WHERE `id` = ' . $id);
                            if ($afr) {
                                echo '<div class="gmenu">' . __('Successful transfer') . '</div><div><a href="?do=dir&amp;id=' . $move . '">' . __('Back') . '</a></div>' . PHP_EOL;
                            }
                        }
                    }
                    break;

                case 'delmove':
                    $child_dir = new Tree($id);
                    $childrens = $child_dir->getChildsDir()->result();
                    $list = $db->query('SELECT `id`, `name` FROM `library_cats` WHERE `dir`=' . $dirtype . ' AND ' . ($dirtype && count($childrens) ? '`id` NOT IN(' . implode(', ', $childrens) . ', ' . $id . ')' : '`id`  != ' . $id));
                    if ($list->rowCount()) {
                        echo '<div class="menu">'
                            . '<h3>' . __('Move to Section') . '</h3>'
                            . '<form action="?act=del&amp;type=dir&amp;id=' . $id . '" method="post">'
                            . '<div><select name="move">';
                        while ($rm = $list->fetch()) {
                            echo '<option value="' . $rm['id'] . '">' . $tools->checkout($rm['name']) . '</option>';
                        }
                        echo '</select></div>'
                            . '<div><input type="hidden" name="mode" value="moveaction" /></div>'
                            . '<div class="bmenu"><input type="submit" name="submit" value="' . __('Approve') . '" /></div>'
                            . '</form>'
                            . '</div>';
                    } else {
                        echo '<div class="rmenu">' . __('There are no Sections for moving') . '</div><div class="bmenu"><a href="?">' . __('Back') . '</a></div>';
                    }
                    break;

                case 'delall':
                    if (! isset($_GET['deldeny'])) {
                        echo '<div class="alarm"><div>' . __('Are you sure you want to delete content?') .
                            '</div><div><a href="?act=del&amp;type=' . $type . '&amp;id=' . $id . '&amp;deldeny&amp;do=delall">' .
                            __('Delete') . '</a> | <a href="?">' . __('Cancel') . '</a></div></div>';
                    } else {
                        $childs = new Tree($id);
                        $deleted = $childs->getAllChildsId()->cleanDir();
                        echo '<div class="gmenu">' . sprintf(
                            __('Successfully deleted:<br>Directories: (%d)<br>Articles: (%d)<br>Tags: (%d)<br>Comments: (%d)<br>Images: (%d)'),
                            $deleted['dirs'],
                            $deleted['texts'],
                            $deleted['tags'],
                            $deleted['comments'],
                            $deleted['images']
                        ) . '</div><div><a href="?">' . __('Back') . '</a></div>' . PHP_EOL;
                    }
                    break;

                default:
                    echo '<div class="alarm">' . __('Section is not empty') . '</div>'
                        . '<div class="menu"><h3>' . __('Select action') . '</h3>'
                        . '<form action="?act=del&amp;type=dir&amp;id=' . $id . '" method="post">'
                        . '<div><input type="radio" name="mode" value="delmove" checked="checked" /> ' . __('Delete with movement') . '</div>'
                        . '<div><input type="radio" name="mode" value="delall" /> <span style="color: red;"> ' . __('Delete all Sections and Articles') . '</span></div>'
                        . '<div class="bmenu"><input type="submit" name="submit" value="' . __('Do') . '" /></div>'
                        . '</form>'
                        . '</div>';
                    break;
            }
        } else {
            $sql = 'DELETE FROM `library_cats` WHERE `id`=' . $id;
            if (! isset($_GET['yes'])) {
                echo '<div class="alarm"><div>' . __('Delete confirmation') .
                    '</div><div><a href="?act=del&amp;type=' . $type . '&amp;id=' . $id . '&amp;yes">' .
                    __('Delete') . '</a> | <a href="?do=dir&amp;id=' . $id . '">' . __('Cancel') . '</a></div></div>';
            }
        }
        break;

    case 'article':
        if ($db->query('SELECT COUNT(*) FROM `library_texts` WHERE `id`=' . $id)->rowCount() == 0) {
            echo $tools->displayError(__('Articles do not exist'));
        } else {
            $sql = 'DELETE FROM `library_texts` WHERE `id`=' . $id;
            if (! isset($_GET['yes'])) {
                echo '<div class="rmenu"><p>' . __('Delete confirmation') . '<br><a href="?act=del&amp;type=' . $type . '&amp;id=' . $id . '&amp;yes">' . __('Delete') . '</a> | <a href="?id=' . $id . '">' . __('Cancel') . '</a></p></div>';
            }
        }
        break;
    case 'image':
        if (! isset($_GET['yes'])) {
            echo '<div class="alarm"><div>' . __('Delete confirmation') .
                '</div><div><a href="?act=del&amp;type=' . $type . '&amp;id=' . $id . '&amp;yes">' .
                __('Delete') . '</a> | <a href="?act=moder&amp;type=article&amp;id=' . $id . '">' . __('Cancel') . '</a></div></div>';
        }
        break;
}
if (isset($_GET['yes']) && $type == 'image') {
    if (file_exists(UPLOAD_PATH . 'library/images/small/' . $id . '.png')) {
        @unlink(UPLOAD_PATH . 'library/images/big/' . $id . '.png');
        @unlink(UPLOAD_PATH . 'library/images/orig/' . $id . '.png');
        @unlink(UPLOAD_PATH . 'library/images/small/' . $id . '.png');
    }
    echo '<div class="gmenu">' . __('Deleted') . '</div><div><a href="?act=moder&amp;type=article&amp;id=' . $id . '">' . __('Back') . '</a></div>' . PHP_EOL;
} elseif (isset($_GET['yes'])) {
    if ($db->exec($sql)) {
        if (file_exists(UPLOAD_PATH . 'library/images/small/' . $id . '.png')) {
            @unlink(UPLOAD_PATH . 'library/images/big/' . $id . '.png');
            @unlink(UPLOAD_PATH . 'library/images/orig/' . $id . '.png');
            @unlink(UPLOAD_PATH . 'library/images/small/' . $id . '.png');
        }
        echo '<div class="gmenu">' . __('Deleted') . '</div><p><a href="?">' . __('Back') . '</a></p>' . PHP_EOL;
    }
}
