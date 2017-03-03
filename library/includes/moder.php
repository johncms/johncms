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

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

use Library\Hashtags;
use Library\Tree;
use Library\Utils;

$obj = new Hashtags($id);
if (isset($_GET['type']) && in_array($_GET['type'], ['dir', 'article'])) {
    $type = $_GET['type'];
} else {
    Utils::redir404();
}

$author = ($type == 'article' && $db->query("SELECT `uploader_id` FROM `library_texts` WHERE `id` = " . $id)->fetchColumn() == $systemUser->id && $systemUser->isValid()) ? 1 : 0;

if (!$adm || (!$author && $type == 'article')) {
    Utils::redir404();
}

if (isset($_POST['submit'])) {
    switch ($type) {
        case 'dir':
            $sql = "UPDATE `library_cats` SET `name`=" . $db->quote($_POST['name']) . ", `description`=" . $db->quote($_POST['description']) . " " . (isset($_POST['move']) && $db->query("SELECT count(*) FROM `library_cats`")->fetchColumn() > 1 ? ', `parent`=' . intval($_POST['move']) : '') . (isset($_POST['dir']) ? ', `dir`=' . intval($_POST['dir']) : '') . (isset($_POST['user_add']) ? ' , `user_add`=' . intval($_POST['user_add']) : '') . " WHERE `id`=" . $id;
            break;

        case 'article':
            $obj->delTags();
            if (isset($_POST['tags'])) {
                $obj->delCache();
                $tags = array_map('trim', explode(',', $_POST['tags']));
                if (sizeof($tags > 0)) {
                    $obj->addTags($tags);
                }
            }

            $image = isset($_FILES['image']['tmp_name']) ? $_FILES['image'] : '';

            $handle = new upload($image);
            if ($handle->uploaded) {
                // Обрабатываем фото
                $handle->file_new_name_body = $id;
                $handle->allowed = [
                    'image/jpeg',
                    'image/gif',
                    'image/png',
                ];
                $handle->file_max_size = 1024 * $config['flsz'];
                $handle->file_overwrite = true;
                $handle->image_x = $handle->image_src_x;
                $handle->image_y = $handle->image_src_y;
                $handle->image_convert = 'png';
                $handle->process('../files/library/images/orig/');
                $err_image = $handle->error;
                $handle->file_new_name_body = $id;
                $handle->file_overwrite = true;
                if ($handle->image_src_y > 240) {
                    $handle->image_resize = true;
                    $handle->image_x = 240;
                    $handle->image_y = $handle->image_src_y * (240 / $handle->image_src_x);
                } else {
                    $handle->image_x = $handle->image_src_x;
                    $handle->image_y = $handle->image_src_y;
                }
                $handle->image_convert = 'png';
                $handle->process('../files/library/images/big/');
                $err_image = $handle->error;
                $handle->file_new_name_body = $id;
                $handle->file_overwrite = true;
                $handle->image_resize = true;
                $handle->image_x = 32;
                $handle->image_y = 32;
                $handle->image_convert = 'png';
                $handle->process('../files/library/images/small/');
                if ($err_image) {
                    echo $tools->displayError(_t('Photo uploading error'));
                }
                $handle->clean();
            }
            $sql = "UPDATE `library_texts` SET `name`=" . $db->quote($_POST['name']) . ", " . ($_POST['text'] != 'do_not_change' ? " `text`=" . $db->quote($_POST['text']) . ", " : '') . " " . (isset($_POST['move']) ? '`cat_id`=' . intval($_POST['move']) . ', ' : '') . " `announce`=" . $db->quote(mb_substr(trim($_POST['announce']),
                    0,
                    500)) . " " . ($adm ? ", `count_views`=" . intval($_POST['count_views']) . ", `premod`=" . intval($_POST['premod']) . ", `comments`=" . (isset($_POST['comments']) ? intval($_POST['comments']) : 0) : '') . " WHERE `id`=" . $id;
            break;
    }
    $db->exec($sql);
    echo '<div>' . _t('Changed') . '</div><div><a href="?do=' . ($type == 'dir' ? 'dir' : 'text') . '&amp;id=' . $id . '">' . _t('Back') . '</a></div>' . PHP_EOL;

} else {
    $child_dir = new Tree($id);
    $childrens = $child_dir->getChildsDir()->result();
    $sqlsel = $db->query("SELECT " . ($type == 'dir' ? '`id`, `parent`' : '`id`') . ", `name` FROM `library_cats` "
        . "WHERE `dir`=" . ($type == 'dir' ? 1 : 0) . ' ' . ($type == 'dir' && sizeof($childrens) ? 'AND `id` NOT IN(' . implode(', ', $childrens) . ')' : ''));
    $row = $db->query("SELECT * FROM `" . ($type == 'article' ? 'library_texts' : 'library_cats') . "` WHERE `id`=" . $id)->fetch();
    $empty = $db->query("SELECT COUNT(*) FROM `library_cats` WHERE `parent`=" . $id)->fetchColumn() > 0
            || $db->query("SELECT COUNT(*) FROM `library_texts` WHERE `cat_id`=" . $id)->fetchColumn() > 0 ? 0 : 1;

    if (!$row) {
        Utils::redir404();
    }

    echo '<div class="phdr"><strong><a href="?">' . _t('Library') . '</a></strong> | '
        . ($type == 'dir' ? _t('Edit Section') : _t('Edit Article'))
        . '</div>'
        . '<form name="form" enctype="multipart/form-data" action="?act=moder&amp;type=' . $type . '&amp;id=' . $id . '" method="post">'
        . '<div class="menu">'
        . ($type == 'article' ? (file_exists('../files/library/images/big/' . $id . '.png')
                ? '<div><img src="../files/library/images/big/' . $id . '.png" alt="screen" />' . '</div>'
                . '<div class="alarm"><a href="?act=del&amp;type=image&amp;id=' . $id . '">Удалить обложку</a></div>'
                : '')
            . '<h3>' . _t('To upload a photo') . '</h3>'
            . '<div><input name="image" type="file" /></div>'
            . '<h3>' . _t('Title') . '</h3>' : '')
        . '<div><input type="text" name="name" value="' . $tools->checkout($row['name']) . '" /></div>'
        . ($type == 'dir' ? '<h3>' . _t('Section description') . '</h3>'
            . '<div><textarea name="description" rows="4" cols="20">' . $tools->checkout($row['description']) . '</textarea></div>' : '')
        . ($type == 'article'
            ? '<h3>' . _t('Announce') . '</h3><div><textarea rows="2" cols="20" name="announce">' . $tools->checkout($row['announce'])
            . '</textarea></div>'
            : '')
        . ($type == 'article' && mb_strlen($row['text']) < 500000
            ? '<h3>' . _t('Text') . '</h3><div>' . $container->get(Johncms\Api\BbcodeInterface::class)->buttons('form',
                'text') . '<textarea rows="5" cols="20" name="text">' . $tools->checkout($row['text'])
            . '</textarea></div>'
            : ($type == 'article' && mb_strlen($row['text']) > 500000
                ? '<div class="alarm">' . _t('The text of the Article can not be edited, a large amount of data !!!') . '</div><input type="hidden" name="text" value="do_not_change" /></div>'
                : ''))
        . ($type == 'article'
            ? '<h3>' . _t('Tags') . '</h3><div><input name="tags" type="text" value="' . $tools->checkout($obj->getAllStatTags()) . '" /></div>'
            : '');
    if ($adm) {
        if ($sqlsel->rowCount() > 1) {
            echo '<h3>' . _t('Move to Section') . '</h3>'
                . '<div><select name="move">'
                . ($type == 'dir'
                    ? '<option ' . ($type == 'dir' && $row['parent'] == 0
                        ? 'selected="selected"'
                        : '')
                    . ' value="0">' . _t('The ROOT') . '</option>'
                    : '');
            while ($res = $sqlsel->fetch()) {
                if ($row['name'] != $res['name']) {
                    echo '<option '
                        . (($type == 'dir' && $row['parent'] == $res['id']) || ($type == 'article' && $row['cat_id'] == $res['id'])
                            ? 'selected="selected" '
                            : '')
                        . 'value="' . $res['id'] . '">' . $tools->checkout($res['name']) . '</option>';
                }
            }
            echo '</select></div>';
        }
        echo (($type == 'dir' && $empty)
                ? '<h3>' . _t('Section type') . '</h3><div><input type="radio" name="dir" value="1" '
                . ($row['dir'] == 1
                    ? 'checked="checked"'
                    : '') . ' />' . _t('Sections') . '</div>'
                . '<div><input type="radio" name="dir" value="0" ' . ($row['dir'] == 0 ? 'checked="checked"' : '') . ' />' . _t('Articles') . '</div>' : '')
            . ($type == 'dir' && $row['dir'] == 0
                ? '<div>' . _t('Allow users to add their Articles?') . '</div><div><input type="radio" name="user_add" value="1" '
                . ($row['user_add'] == 1 ? 'checked="checked"' : '') . ' /> ' . _t('Yes') . '</div><div><input type="radio" name="user_add" value="0" '
                . ($row['user_add'] == 0 ? 'checked="checked"' : '') . ' /> ' . _t('No') . '</div>' : '')
            . ($type == 'article' ? '<div class="' . ($row['premod'] > 0 ? 'green' : 'red') . '"><input type="checkbox" name="premod" value="1" ' . ($row['premod'] > 0
                    ? 'checked="checked"' : '') . '/> ' . _t('Verified') . '</div>'
                . '<div class="' . ($row['comments'] > 0 ? 'green' : 'red') . '"><input type="checkbox" name="comments" value="1" '
                . ($row['comments'] > 0 ? 'checked="checked"' : '') . ' /> ' . _t('Commenting on the Article') . '</div>'
                . '<div class="rmenu">'
                . '<h3>' . _t('Number of readings')
                . '</h3><div><input type="text" name="count_views" value="' . intval($row['count_views']) . '" /></div></div>' . PHP_EOL : '');
    }
    echo '<div class="bmenu"><input type="submit" name="submit" value="' . _t('Save') . '" />'
        . '</div></div></form>' . PHP_EOL
        . '<p><a href="?do=' . ($type == 'dir' ? 'dir' : 'text') . '&amp;id=' . $id . '">' . _t('Back') . '</a></p>' . PHP_EOL;
}
