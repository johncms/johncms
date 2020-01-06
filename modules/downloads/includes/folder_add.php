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

/**
 * @var PDO $db
 * @var Johncms\System\Users\User $user
 */

$nav_chain->add(_t('Create Folder'));

if (! $id) {
    $load_cat = $files_path;
} else {
    $req_down = $db->query("SELECT * FROM `download__category` WHERE `id` = '" . $id . "' LIMIT 1");
    $res_down = $req_down->fetch();

    if (! $req_down->rowCount() || ! is_dir($res_down['dir'])) {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => _t('Create Folder'),
                'type'          => 'alert-danger',
                'message'       => _t('The directory does not exist'),
                'back_url'      => $urls['downloads'],
                'back_url_name' => _t('Downloads'),
            ]
        );
        exit;
    }

    $load_cat = $res_down['dir'];
}

if (isset($_POST['submit'])) {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $rus_name = isset($_POST['rus_name']) ? trim($_POST['rus_name']) : '';
    $desc = isset($_POST['desc']) ? trim($_POST['desc']) : '';
    $user_down = isset($_POST['user_down']) ? 1 : 0;
    $format = $user_down && isset($_POST['format']) ? trim($_POST['format']) : false;
    $error = [];

    if (empty($name)) {
        $error[] = _t('The required fields are not filled');
    }

    if (preg_match('/[^0-9a-zA-Z]+/', $name)) {
        $error[] = _t('Invalid characters');
    }

    if ($user->rights === 9 && $user_down) {
        foreach (explode(',', $format) as $value) {
            if (! in_array(trim($value), $defaultExt, true)) {
                $error[] = _t('You can write only the following extensions') . ': ' . implode(', ', $defaultExt);
                break;
            }
        }
    }

    if ($error) {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => _t('Create Folder'),
                'type'          => 'alert-danger',
                'message'       => $error,
                'back_url'      => '?act=folder_add&amp;id=' . $id,
                'back_url_name' => _t('Repeat'),
            ]
        );
        exit;
    }

    if (empty($rus_name)) {
        $rus_name = $name;
    }

    $dir = false;
    $load_cat .= '/' . $name;

    if (! is_dir($load_cat)) {
        $dir = mkdir($load_cat, 0777);
    }

    if ($dir) {
        chmod($load_cat, 0777);

        $stmt = $db->prepare(
            '
                INSERT INTO `download__category`
                (`refid`, `dir`, `sort`, `name`, `desc`, `field`, `text`, `rus_name`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            '
        );

        $stmt->execute(
            [
                $id,
                $load_cat,
                time(),
                $name,
                $desc,
                $user_down,
                $format,
                $rus_name,
            ]
        );
        $cat_id = $db->lastInsertId();

        echo $view->render(
            'system::pages/result',
            [
                'title'         => _t('Create Folder'),
                'type'          => 'alert-success',
                'message'       => _t('The Folder is created'),
                'back_url'      => '?id=' . $cat_id,
                'back_url_name' => _t('Continue'),
            ]
        );
    } else {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => _t('Create Folder'),
                'type'          => 'alert-danger',
                'message'       => _t('Error creating categories'),
                'back_url'      => '?act=folder_add&amp;id=' . $id,
                'back_url_name' => _t('Repeat'),
            ]
        );
    }
} else {
    $folder_params = [
        'name'      => '',
        'rus_name'  => '',
        'desc'      => '',
        'user_down' => '',
        'format'    => '',
    ];
    echo $view->render(
        'downloads::folder_form',
        [
            'title'         => _t('Downloads'),
            'page_title'    => _t('Downloads'),
            'id'            => $id,
            'urls'          => $urls,
            'folder_params' => $folder_params,
            'action_url'    => '?act=folder_add&amp;id=' . $id,
            'extensions'    => implode(', ', $defaultExt),
            'edit_form'     => false,
        ]
    );
}
