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
 * @var Johncms\System\Legacy\Tools $tools
 */

if ($id) {
    $error = false;

    // Скачивание прикрепленного файла Форума
    $req = $db->query("SELECT * FROM `cms_forum_files` WHERE `id` = '${id}'");

    if ($req->rowCount()) {
        $res = $req->fetch();

        if (file_exists(UPLOAD_PATH . 'forum/attach/' . $res['filename'])) {
            $dlcount = $res['dlcount'] + 1;
            $db->exec("UPDATE `cms_forum_files` SET  `dlcount` = '${dlcount}' WHERE `id` = '${id}'");
            header('location: ../upload/forum/attach/' . $res['filename']); //TODO: Разобраться со ссылкой
        } else {
            $error = true;
        }
    } else {
        $error = true;
    }

    if ($error) {
        http_response_code(404);
        echo $view->render(
            'system::pages/result',
            [
                'title'         => __('Download file'),
                'type'          => 'alert-danger',
                'message'       => __('File does not exist'),
                'back_url'      => '/forum/',
                'back_url_name' => __('Forum'),
            ]
        );
    }
} else {
    header('location: ./');
}
