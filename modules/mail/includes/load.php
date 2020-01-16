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

$textl = __('Mail');

if ($id) {
    $req = $db->query("SELECT * FROM `cms_mail` WHERE (`user_id`='" . $user->id . "' OR `from_id`='" . $user->id . "') AND `id` = '${id}' AND `file_name` != '' AND `delete`!='" . $user->id . "' LIMIT 1");

    if (! $req->rowCount()) {
        //Выводим ошибку
        echo $view->render('system::app/old_content', [
            'title'   => $textl,
            'content' => $tools->displayError(__('Such file does not exist')),
        ]);
        exit;
    }

    $res = $req->fetch();

    if (file_exists(UPLOAD_PATH . 'mail/' . $res['file_name'])) {
        $db->exec("UPDATE `cms_mail` SET `count` = `count`+1 WHERE `id` = '${id}' LIMIT 1");
        header('Location: ../upload/mail/' . $res['file_name']);
        exit;
    }
    echo $tools->displayError(__('Such file does not exist'));
} else {
    echo $tools->displayError(__('No file selected'));
}
