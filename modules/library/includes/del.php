<?php

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

defined('_IN_JOHNCMS') || die('Error: restricted access');

use Library\Tree;
use Library\Utils;

if (! $adm) {
    Utils::redir404();
}

echo '<div class="phdr"><strong><a href="?">' . _t('Library') . '</a></strong> | ' . _t('Delete') . '</div>';

if (isset($_GET['type']) && in_array($_GET['type'], ['dir', 'article', 'image'])) {
    $type = $_GET['type'];
} else {
    Utils::redir404();
}
$change = ($type === 'dir'
    ? $db->query('SELECT COUNT(*) FROM `library_cats` WHERE `parent`=' . $id)->fetchColumn() > 0
    || $db->query('SELECT COUNT(*) FROM `library_texts` WHERE `cat_id`=' . $id)->fetchColumn() > 0
        ? 0
        : 1
    : '');

switch ($type) {
    case 'dir':
        if ($db->query('SELECT COUNT(*) FROM `library_cats` WHERE `id`=' . $id)->fetchColumn() === 0) {
            echo $tools->displayError(_t('Section does not exist'));
        } elseif (! $change) {
            $mode = $_POST['mode'] ?? ($do ?? false);
            $dirtype = $db->query('SELECT `dir` FROM `library_cats` WHERE `id` = ' . $id . ' LIMIT 1')->fetchColumn();
            switch ($mode) {
                case 'moveaction':
                    if (! isset($_GET['movedeny'])) {
                        echo '<div class="alarm"><div>' . _t('Are you sure you want to move the contents?') .
                            '</div><div><a href="?act=del&amp;type=' . $type . '&amp;id=' . $id . '&amp;movedeny&amp;do=moveaction&amp;move=' .
                            (int) ($_POST['move']) . '">' . _t('Move') . '</a> | <a href="?">' . _t('Cancel') . '</a></div></div>';
                    } else {
                        $move = (int) ($_GET['move']);

                        $afr = ($dirtype
                            ? $db->exec('UPDATE `library_cats` SET `parent`=' . $move . ' WHERE `parent` = ' . $id)
                            : $db->exec('UPDATE `library_texts` SET `cat_id` = ' . $move . ' WHERE `cat_id` = ' . $id));

                        if ($afr && $db->exec('DELETE FROM `library_cats` WHERE `id` = ' . $id)) {
                            echo '<div class="gmenu">' . _t('Successful transfer') . '</div><div><a href="?do=dir&amp;id=' . $move . '">' . _t('Back') . '</a></div>' . PHP_EOL;
                        }
                    }
                    break;

                case 'delmove':
                    $child_dir = new Tree($id);
                    $childrens = $child_dir->getChildsDir()->result();
                    $list = $db->query(
                        'SELECT `id`, `name` FROM `library_cats` WHERE `dir`=' . $dirtype . ' AND '
                        . ($dirtype && count($childrens)
                            ? '`id` NOT IN(' . implode(', ', $childrens) . ', ' . $id . ')'
                            : '`id`  != ' . $id)
                    );
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
                    if (! isset($_GET['deldeny'])) {
                        echo '<div class="alarm"><div>' . _t('Are you sure you want to delete content?') .
                            '</div><div><a href="?act=del&amp;type=' . $type . '&amp;id=' . $id . '&amp;deldeny&amp;do=delall">' .
                            _t('Delete') . '</a> | <a href="?">' . _t('Cancel') . '</a></div></div>';
                    } else {
                        $childs = new Tree($id);
                        $args = [t('Successfully deleted:<br>Directories: (%d)<br>Articles: (%d)<br>Tags: (%d)<br>Comments: (%d)<br>Images: (%d)')] + $childs->getAllChildsId()->cleanDir();
                        echo '<div class="gmenu">'
                            . sprintf(...$args)
                            . '</div><div><a href="?">' . _t('Back') . '</a></div>' . PHP_EOL;
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
            $sql = 'DELETE FROM `library_cats` WHERE `id`=' . $id;
            if (! isset($_GET['yes'])) {
                echo '<div class="alarm"><div>' . _t('Delete confirmation') .
                    '</div><div><a href="?act=del&amp;type=' . $type . '&amp;id=' . $id . '&amp;yes">' .
                    _t('Delete') . '</a> | <a href="?do=dir&amp;id=' . $id . '">' . _t('Cancel') . '</a></div></div>';
            }
        }
        break;

    case 'article':
        if ($db->query('SELECT COUNT(*) FROM `library_texts` WHERE `id`=' . $id)->rowCount() === 0) {
            echo $tools->displayError(_t('Articles do not exist'));
        } else {
            $sql = 'DELETE FROM `library_texts` WHERE `id`=' . $id;
            if (! isset($_GET['yes'])) {
                echo '<div class="rmenu"><p>' . _t('Delete confirmation') . '<br><a href="?act=del&amp;type=' . $type . '&amp;id=' . $id . '&amp;yes">' . _t('Delete') . '</a> | <a href="?id=' . $id . '">' . _t('Cancel') . '</a></p></div>';
            }
        }
        break;
    case 'image':
        if (! isset($_GET['yes'])) {
            echo '<div class="alarm"><div>' . _t('Delete confirmation') .
                '</div><div><a href="?act=del&amp;type=' . $type . '&amp;id=' . $id . '&amp;yes">' .
                _t('Delete') . '</a> | <a href="?act=moder&amp;type=article&amp;id=' . $id . '">' . _t('Cancel') . '</a></div></div>';
        }
        break;
}
if (isset($_GET['yes']) && $type === 'image') {
    Utils::unlinkImages($id);
    echo '<div class="gmenu">' . _t('Deleted') . '</div><div><a href="?act=moder&amp;type=article&amp;id=' . $id . '">' . _t('Back') . '</a></div>' . PHP_EOL;
} elseif (isset($_GET['yes'])) {
    if ($db->exec($sql)) {
        Utils::unlinkImages($id);
        echo '<div class="gmenu">' . _t('Deleted') . '</div><p><a href="?">' . _t('Back') . '</a></p>' . PHP_EOL;
    }
}
