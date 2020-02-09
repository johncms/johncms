<?php

/**
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

if (isset($_GET['type']) && in_array($_GET['type'], ['dir', 'article', 'image'])) {
    $type = $_GET['type'];
} else {
    Utils::redir404();
}
$error = false;
$message = false;
$dirchange = false;
$moving = false;
$delmove = false;
$deldeny = false;
$article = false;
$image = false;
$deny = false;
$mode = false;
$move = false;

$title = __('Delete');
$nav_chain->add($title);

$change = ($type === 'dir'
    ? $db->query('SELECT COUNT(*) FROM `library_cats` WHERE `parent` = ' . $id)->fetchColumn() > 0
    || $db->query('SELECT COUNT(*) FROM `library_texts` WHERE `cat_id` = ' . $id)->fetchColumn() > 0
        ? 0
        : 1
    : '');

switch ($type) {
    case 'dir':
        if ($db->query('SELECT COUNT(*) FROM `library_cats` WHERE `id` = ' . $id)->fetchColumn() === 0) {
            $error = $tools->displayError(__('Section does not exist'));
        } elseif (! $change) {
            $dirchange = true;
            $mode = $_POST['mode'] ?? ($do ?? false);
            $dirtype = $db->query('SELECT `dir` FROM `library_cats` WHERE `id` = ' . $id . ' LIMIT 1')->fetchColumn();
            switch ($mode) {
                case 'moveaction':
                    if (isset($_GET['movedeny'])) {
                        $move = (int) ($_GET['move']);
                        $afr = ($dirtype
                            ? $db->exec('UPDATE `library_cats` SET `parent` = ' . $move . ' WHERE `parent` = ' . $id)
                            : $db->exec('UPDATE `library_texts` SET `cat_id` = ' . $move . ' WHERE `cat_id` = ' . $id));
                        $moving = $afr && $db->exec('DELETE FROM `library_cats` WHERE `id` = ' . $id);
                    }
                    break;

                case 'delmove':
                    $child_dir = new Tree($id);
                    $childrens = $child_dir->getChildsDir()->result();
                    $list = $db->query(
                        'SELECT `id`, `name` FROM `library_cats` WHERE `dir` = ' . $dirtype . ' AND '
                        . ($dirtype && count($childrens)
                            ? '`id` NOT IN(' . implode(', ', $childrens) . ', ' . $id . ')'
                            : '`id`  != ' . $id)
                    );
                    if ($list->rowCount()) {
                        $delmove = [];
                        while ($rm = $list->fetch()) {
                            $delmove[$rm['id']] = $tools->checkout($rm['name']);
                        }
                    }
                    break;

                case 'delall':
                    if (isset($_GET['deldeny'])) {
                        $childs = new Tree($id);
                        $args = array_merge([__('Successfully deleted:<br>Directories: (%d)<br>Articles: (%d)<br>Tags: (%d)<br>Comments: (%d)<br>Images: (%d)')], array_values($childs->getAllChildsId()->cleanDir()));
                        $deldeny = sprintf(...$args);
                        // TODO: Запилить удаление рэйтинга
                    }
                    break;
            }
        } else {
            $sql = 'DELETE FROM `library_cats` WHERE `id` = ' . $id;
        }
        break;

    case 'article':
        if ($db->query('SELECT COUNT(*) FROM `library_texts` WHERE `id` = ' . $id)->rowCount() === 0) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'   => $title,
                    'type'    => 'alert-danger',
                    'message' => __('Articles do not exist'),
                ]
            );
        } else {
            $sql = 'DELETE FROM `library_texts` WHERE `id` = ' . $id;
            if (isset($_GET['yes'])) {
                $article = true;
                Utils::unlinkImages($id);
            }
        }
        break;

    case 'image':
        if (isset($_GET['yes'])) {
            $image = true;
            Utils::unlinkImages($id);
        }
        break;
}
if (isset($_GET['yes']) && $db->exec($sql)) {
    $deny = true;
}

echo $view->render(
    'library::del',
    [
        'title'      => $title,
        'page_title' => $title,
        'id'         => $id,
        'type'       => $type,
        'error'      => $error,
        'mode'       => $mode,
        'moving'     => $moving,
        'move'       => $move,
        'delmove'    => $delmove,
        'deldeny'    => $deldeny,
        'article'    => $article,
        'image'      => $image,
        'deny'       => $deny,
        'dirchange'  => $dirchange,
    ]
);
