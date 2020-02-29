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

use Johncms\System\Http\Request;
use Library\Hashtags;
use Library\Tree;
use Library\Utils;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @var PDO $db
 * @var Johncms\System\Users\User $user
 * @var Johncms\System\View\Render $view
 * @var Request $request
 */

$request = di(Request::class);

if (isset($_GET['type']) && in_array($_GET['type'], ['dir', 'article'])) {
    $type = $_GET['type'];
} else {
    Utils::redir404();
}

$row = false;
$select = false;
$empty = false;
$bbcode = false;

$author = (
    ($user->isValid() && $db->query('SELECT `uploader_id` FROM `library_texts` WHERE `id` = ' . $id)->fetchColumn() === $user->id) && $type === 'article')
    ? 1
    : 0;

if (! $adm && (! $author && $type === 'article')) {
    Utils::redir404();
}

if (isset($_POST['submit'])) {
    $placeholders = [];
    if ($type === 'dir') {
        $fields = [
            'name'        => $_POST['name'],
            'description' => $_POST['description'],
            'parent'      => (isset($_POST['move']) && $db->query('SELECT COUNT(*) FROM `library_cats`')->fetchColumn() > 1 ? $_POST['move'] : null),
            'dir'         => ($_POST['dir'] ?? null),
            'user_add'    => ($_POST['user_add'] ?? null),
        ];
    } else {
        if (isset($_POST['tags'])) {
            $obj = new Hashtags($id);
            $obj->delTags();
            $obj->delCache();
            $tags = array_map('trim', explode(',', $_POST['tags']));
            if (count($tags)) {
                $obj->addTags($tags);
            }
        }

        $files = $request->getUploadedFiles();
        /** @var GuzzleHttp\Psr7\UploadedFile $screen */
        $screen = $files['image'] ?? false;

        if ($screen && $screen->getClientFilename()) {
            try {
                Utils::imageUpload($id, $screen);
            } catch (Exception $exception) {
                $error = __('Photo uploading error');
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
                'count_views' => $request->getPost('count_views', 0, FILTER_VALIDATE_INT),
                'premod'      => $request->getPost('premod', 0, FILTER_VALIDATE_INT),
                'comments'    => $request->getPost('comments', '', FILTER_SANITIZE_STRING),
            ];
            $fields += $fields_adm;
        }
    }

    $sql = 'UPDATE ' . ($type === 'dir' ? '`library_cats`' : '`library_texts`') . ' SET ';

    foreach ($fields as $field => $value) {
        if (null !== $value) {
            $sql .= '`' . $field . '` = ?, ';
            $placeholders[] = $value;
        }
    }

    $sql = rtrim($sql, ' ,') . ' WHERE `id` = ' . $id;

    $db->prepare($sql)->execute($placeholders);
} else {
    $child_dir = new Tree($id);
    $childrens = $child_dir->getChildsDir()->result();

    $sqlsel = $db->query(
        'SELECT ' . ($type === 'dir' ? '`id`, `parent`' : '`id`') . ', `name` FROM `library_cats` '
        . 'WHERE `dir` = ' . ($type === 'dir' ? 1 : 0) . ' ' . ($type === 'dir' && count($childrens) ? 'AND `id` NOT IN(' . implode(', ', $childrens) . ')' : '')
    );

    $row = $db->query('SELECT * FROM `' . ($type === 'article' ? 'library_texts' : 'library_cats') . '` WHERE `id` = ' . $id)->fetch();

    $empty = $db->query('SELECT COUNT(*) FROM `library_cats` WHERE `parent`=' . $id)->fetchColumn() > 0
    || $db->query('SELECT COUNT(*) FROM `library_texts` WHERE `cat_id`=' . $id)->fetchColumn() > 0 ? 0 : 1;

    if (! $row) {
        Utils::redir404();
    }

    $empty = ($type === 'dir' && $empty);
    $row['cover'] = file_exists(UPLOAD_PATH . 'library/images/small/' . $id . '.png');
    $row['name'] = $tools->checkout($row['name']);
    $row['description'] = isset($row['description']) ? $tools->checkout($row['description']) : null;
    $row['announce'] = isset($row['announce']) ? $tools->checkout($row['announce']) : null;
    $bbcode = di(Johncms\System\Legacy\Bbcode::class)->buttons('form', 'text');
    $row['text'] = isset($row['text']) ? $tools->checkout($row['text']) : null;
    $obj = new Hashtags($id);
    $row['tags'] = $type === 'article' && $obj->getAllStatTags() ? $tools->checkout($obj->getAllStatTags()) : null;

    if ($adm) {
        if ($sqlsel->rowCount() > 1) {
            $select = [];
            $select[] = ($type === 'dir'
                ? '<option ' . ($type === 'dir' && $row['parent'] === 0
                    ? 'selected="selected"'
                    : '')
                . ' value="0">' . __('The ROOT') . '</option>'
                : '');
            while ($res = $sqlsel->fetch()) {
                if ($row['name'] !== $res['name']) {
                    $select[] = '<option '
                        . (($type === 'dir' && $row['parent'] === $res['id']) || ($type === 'article' && $row['cat_id'] === $res['id'])
                            ? 'selected="selected" '
                            : '')
                        . 'value="' . $res['id'] . '">' . $tools->checkout($res['name']) . '</option>';
                }
            }
        }
    }
}
$title = ($type === 'dir' ? __('Edit Section') : __('Edit Article'));
$nav_chain->add($title);

echo $view->render(
    'library::moder',
    [
        'title'      => $title,
        'page_title' => $page_title ?? $title,
        'res'        => $row,
        'empty'      => $empty,
        'type'       => $type,
        'id'         => $id,
        'adm'        => $adm,
        'select'     => $select,
        'bbcode'     => $bbcode,
    ]
);
