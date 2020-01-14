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

use Library\Hashtags;
use Library\Tree;
use Library\Utils;
use Psr\Http\Message\ServerRequestInterface;

$request = di(ServerRequestInterface::class);

/**
 * @var PDO $db
 * @var Johncms\System\Users\User $user
 * @var Johncms\System\View\Render $view
 * @var  ServerRequestInterface $request
 */

$obj = new Hashtags($id);
if (isset($_GET['type']) && in_array($_GET['type'], ['dir', 'article'])) {
    $type = $_GET['type'];
} else {
    Utils::redir404();
}

$author = (
    ($user->isValid() && $db->query('SELECT `uploader_id` FROM `library_texts` WHERE `id` = ' . $id)->fetchColumn() === $user->id) && $type === 'article')
    ? 1
    : 0;

if (! $adm || (! $author && $type === 'article')) {
    Utils::redir404();
}

if (isset($_POST['submit'])) {
    $placeholders = [];
    switch ($type) {
        case 'dir':
            $fields = [
                'name'        => $_POST['name'],
                'description' => $_POST['description'],
                'parent'      => (isset($_POST['move']) && $db->query('SELECT COUNT(*) FROM `library_cats`')->fetchColumn() > 1 ? $_POST['move'] : null),
                'dir'         => ($_POST['dir'] ?? null),
                'user_add'    => ($_POST['user_add'] ?? null),
            ];

            $sql = 'UPDATE `library_cats` SET ';

            foreach ($fields as $field => $value) {
                if (null !== $value) {
                    $sql .= '`' . $field . '` = ?, ';
                    $placeholders[] = $value;
                }
            }

            $sql = rtrim($sql, ',') . ' WHERE `id` = ' . $id;

            break;

        case 'article':
            $obj->delTags();
            if (isset($_POST['tags'])) {
                $obj->delCache();
                $tags = array_map('trim', explode(',', $_POST['tags']));
                if (count($tags)) {
                    $obj->addTags($tags);
                }
            }

            $files = $request->getUploadedFiles();
            /** @var GuzzleHttp\Psr7\UploadedFile $screen */
            $screen = $files['image'] ?? false;

            if ($screen->getClientFilename()) {
                try {
                    Utils::imageUpload($id, $screen);
                } catch (Exception $exception) {
                    $error = _t('Photo uploading error');
                }
            }

            $fields = [
                'name'     => $_POST['name'],
                'text'     => ($_POST['text'] !== 'do_not_change' ? $_POST['text'] : null),
                'cat_id'   => $_POST['move'] ?? null,
                'announce' => $_POST['announce'] ? mb_substr(trim($_POST['announce']), 0, 500) : null,
            ];

            if ($adm) {
                $fields_adm = [
                    'count_views' => $_POST['count_views'],
                    'premod'      => $_POST['premod'],
                    'comments'    => $_POST['comments'] ?? 0,
                ];
                $fields += $fields_adm;
            }

            $sql = 'UPDATE `library_cats` SET ';

            foreach ($fields as $field => $value) {
                if (null !== $value) {
                    $sql .= '`' . $field . '` = ?, ';
                    $placeholders[] = $value;
                }
            }

            $sql = rtrim($sql, ',') . ' WHERE `id` = ' . $id;

            break;
    }

    $db->prepare($sql)->execute($placeholders); // посмотреть косяк

    echo '<div>' . _t('Changed') . '</div><div><a href="?do=' . ($type === 'dir' ? 'dir' : 'text') . '&amp;id=' . $id . '">' . _t('Back') . '</a></div>' . PHP_EOL;
} else {
    $child_dir = new Tree($id);
    $childrens = $child_dir->getChildsDir()->result();

    $sqlsel = $db->query(
        'SELECT ' . ($type === 'dir' ? '`id`, `parent`' : '`id`') . ', `name` FROM `library_cats` '
        . 'WHERE `dir` = ' . ($type === 'dir' ? 1 : 0) . ' ' . ($type === 'dir' && count($childrens) ? 'AND `id` NOT IN(' . implode(', ', $childrens) . ')' : '')
    );

    $row = $db->query('SELECT * FROM `' . ($type === 'article' ? 'library_texts' : 'library_cats') . '` WHERE `id`=' . $id)->fetch();

    $empty = $db->query('SELECT COUNT(*) FROM `library_cats` WHERE `parent`=' . $id)->fetchColumn() > 0
    || $db->query('SELECT COUNT(*) FROM `library_texts` WHERE `cat_id`=' . $id)->fetchColumn() > 0 ? 0 : 1;

    if (! $row) {
        Utils::redir404();
    }

    echo '<div class="phdr"><strong><a href="?">' . _t('Library') . '</a></strong> | '
        . ($type === 'dir' ? _t('Edit Section') : _t('Edit Article'))
        . '</div>'
        . '<form name="form" enctype="multipart/form-data" action="?act=moder&amp;type=' . $type . '&amp;id=' . $id . '" method="post">'
        . '<div class="menu">'
        . ($type === 'article' ? (file_exists(UPLOAD_PATH . 'library/images/big/' . $id . '.png')
                ? '<div><img src="../upload/library/images/big/' . $id . '.png" alt="screen" />' . '</div>'
                . '<div class="alarm"><a href="?act=del&amp;type=image&amp;id=' . $id . '">Удалить обложку</a></div>'
                : '')
            . '<h3>' . _t('To upload a photo') . '</h3>'
            . '<div><input name="image" type="file" /></div>'
            . '<h3>' . _t('Title') . '</h3>' : '')
        . '<div><input type="text" name="name" value="' . $tools->checkout($row['name']) . '" /></div>'
        . ($type === 'dir' ? '<h3>' . _t('Section description') . '</h3>'
            . '<div><textarea name="description" rows="4" cols="20">' . $tools->checkout($row['description']) . '</textarea></div>' : '')
        . ($type === 'article'
            ? '<h3>' . _t('Announce') . '</h3><div><textarea rows="2" cols="20" name="announce">' . $tools->checkout($row['announce'])
            . '</textarea></div>'
            : '')
        . ($type === 'article' && mb_strlen($row['text']) < 500000
            ? '<h3>' . _t('Text') . '</h3><div>' . di(Johncms\System\Legacy\Bbcode::class)->buttons(
                'form',
                'text'
            ) . '<textarea rows="5" cols="20" name="text">' . $tools->checkout($row['text'])
            . '</textarea></div>'
            : ($type === 'article' && mb_strlen($row['text']) > 500000
                ? '<div class="alarm">' . _t('The text of the Article can not be edited, a large amount of data !!!') . '</div><input type="hidden" name="text" value="do_not_change" /></div>'
                : ''))
        . ($type === 'article'
            ? '<h3>' . _t('Tags') . '</h3><div><input name="tags" type="text" value="' . $tools->checkout((string) $obj->getAllStatTags()) . '" /></div>'
            : '');
    if ($adm) {
        if ($sqlsel->rowCount() > 1) {
            echo '<h3>' . _t('Move to Section') . '</h3>'
                . '<div><select name="move">'
                . ($type === 'dir'
                    ? '<option ' . ($type === 'dir' && $row['parent'] === 0
                        ? 'selected="selected"'
                        : '')
                    . ' value="0">' . _t('The ROOT') . '</option>'
                    : '');
            while ($res = $sqlsel->fetch()) {
                if ($row['name'] !== $res['name']) {
                    echo '<option '
                        . (($type === 'dir' && $row['parent'] === $res['id']) || ($type === 'article' && $row['cat_id'] === $res['id'])
                            ? 'selected="selected" '
                            : '')
                        . 'value="' . $res['id'] . '">' . $tools->checkout($res['name']) . '</option>';
                }
            }
            echo '</select></div>';
        }
        echo (($type === 'dir' && $empty)
                ? '<h3>' . _t('Section type') . '</h3><div><input type="radio" name="dir" value="1" '
                . ($row['dir'] === 1
                    ? 'checked="checked"'
                    : '') . ' />' . _t('Sections') . '</div>'
                . '<div><input type="radio" name="dir" value="0" ' . ($row['dir'] === 0 ? 'checked="checked"' : '') . ' />' . _t('Articles') . '</div>' : '')
            . ($type === 'dir' && $row['dir'] === 0
                ? '<div>' . _t('Allow users to add their Articles?') . '</div><div><input type="radio" name="user_add" value="1" '
                . ($row['user_add'] === 1 ? 'checked="checked"' : '') . ' /> ' . _t('Yes') . '</div><div><input type="radio" name="user_add" value="0" '
                . ($row['user_add'] === 0 ? 'checked="checked"' : '') . ' /> ' . _t('No') . '</div>' : '')
            . ($type === 'article' ? '<div class="' . ($row['premod'] > 0 ? 'green' : 'red') . '"><input type="checkbox" name="premod" value="1" ' . ($row['premod'] > 0
                    ? 'checked="checked"' : '') . '/> ' . _t('Verified') . '</div>'
                . '<div class="' . ($row['comments'] > 0 ? 'green' : 'red') . '"><input type="checkbox" name="comments" value="1" '
                . ($row['comments'] > 0 ? 'checked="checked"' : '') . ' /> ' . _t('Commenting on the Article') . '</div>'
                . '<div class="rmenu">'
                . '<h3>' . _t('Number of readings')
                . '</h3><div><input type="text" name="count_views" value="' . (int) ($row['count_views']) . '" /></div></div>' . PHP_EOL : '');
    }
    echo '<div class="bmenu"><input type="submit" name="submit" value="' . _t('Save') . '" />'
        . '</div></div></form>' . PHP_EOL
        . '<p><a href="?do=' . ($type === 'dir' ? 'dir' : 'text') . '&amp;id=' . $id . '">' . _t('Back') . '</a></p>' . PHP_EOL;
}
