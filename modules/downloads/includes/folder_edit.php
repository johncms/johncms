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


$req = $db->query('SELECT * FROM `download__category` WHERE `id` = ' . $id);
$res = $req->fetch();
$error = [];

if (! $req->rowCount() || ! is_dir($res['dir'])) {
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('Edit Folder'),
            'type'          => 'alert-danger',
            'message'       => __('The directory does not exist'),
            'back_url'      => $urls['downloads'],
            'back_url_name' => __('Downloads'),
        ]
    );
    exit;
}

// Сдвиг категорий
if (isset($_GET['up'])) {
    $order = 'DESC';
    $val = '<';
} elseif (isset($_GET['down'])) {
    $order = 'ASC';
    $val = '>';
}

if (isset($_GET['up']) || isset($_GET['down'])) {
    $req_two = $db->query("SELECT * FROM `download__category` WHERE `refid` = '" . $res['refid'] . "' AND `sort` ${val} '" . $res['sort'] . "' ORDER BY `sort` ${order} LIMIT 1");

    if ($req_two->rowCount()) {
        $res_two = $req_two->fetch();
        $db->exec("UPDATE `download__category` SET `sort` = '" . $res_two['sort'] . "' WHERE `id` = '" . $id . "' LIMIT 1");
        $db->exec("UPDATE `download__category` SET `sort` = '" . $res['sort'] . "' WHERE `id` = '" . $res_two['id'] . "' LIMIT 1");
    }

    header('location: ?id=' . $res['refid']);
    exit;
}

// Изменяем данные
if (isset($_POST['submit'])) {
    $rus_name = isset($_POST['rus_name']) ? trim($_POST['rus_name']) : '';

    if (empty($rus_name)) {
        $error[] = __('The required fields are not filled');
    }

    $error_format = false;

    if ($user->rights === 9 && isset($_POST['user_down'])) {
        $format = isset($_POST['format']) ? trim($_POST['format']) : false;
        $format_array = explode(', ', $format);
        foreach ($format_array as $value) {
            if (! in_array($value, $defaultExt, true)) {
                $error_format .= 1;
            }
        }
        $user_down = 1;
        $format_files = htmlspecialchars($format);
    } else {
        $user_down = 0;
        $format_files = '';
    }

    if ($error_format) {
        $error[] = __('You can write only the following extensions') . ': ' . implode(', ', $defaultExt);
    }

    if (! empty($error)) {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => __('Create Folder'),
                'type'          => 'alert-danger',
                'message'       => $error,
                'back_url'      => '?act=folder_edit&amp;id=' . $id,
                'back_url_name' => __('Repeat'),
            ]
        );
        exit;
    }

    $desc = isset($_POST['desc']) ? trim($_POST['desc']) : '';

    $stmt = $db->prepare(
        '
            UPDATE `download__category` SET
            `field`    = ?,
            `text`     = ?,
            `desc`     = ?,
            `rus_name` = ?
            WHERE `id` = ?
        '
    );

    $stmt->execute(
        [
            $user_down,
            $format_files,
            $desc,
            $rus_name,
            $id,
        ]
    );

    header('location: ?id=' . $id);
} else {
    $folder_params = [
        'name'      => '',
        'rus_name'  => htmlspecialchars($res['rus_name']),
        'desc'      => htmlspecialchars($res['desc']),
        'user_down' => $res['field'],
        'format'    => htmlspecialchars($res['text']),
    ];
    echo $view->render(
        'downloads::folder_form',
        [
            'title'         => __('Downloads'),
            'page_title'    => __('Downloads'),
            'id'            => $id,
            'urls'          => $urls,
            'folder_params' => $folder_params,
            'action_url'    => '?act=folder_edit&amp;id=' . $id,
            'extensions'    => implode(', ', $defaultExt),
            'edit_form'     => true,
        ]
    );
}
