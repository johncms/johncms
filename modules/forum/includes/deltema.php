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
 * @var Johncms\System\Utility\Tools $tools
 * @var Johncms\System\Users\User $user
 */

if ($user->rights == 3 || $user->rights >= 6) {
    if (! $id) {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => _t('Delete Topic'),
                'type'          => 'alert-danger',
                'message'       => _t('Wrong data'),
                'back_url'      => '/forum/',
                'back_url_name' => _t('Back'),
            ]
        );
        exit;
    }

    // Проверяем, существует ли тема
    $req = $db->query("SELECT * FROM `forum_topic` WHERE `id` = '${id}'");

    if (! $req->rowCount()) {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => _t('Curators'),
                'page_title'    => _t('Curators'),
                'type'          => 'alert-danger',
                'message'       => _t('Topic has been deleted or does not exists'),
                'back_url'      => '/forum/',
                'back_url_name' => _t('Back'),
            ]
        );
        exit;
    }

    $res = $req->fetch();

    if (isset($_POST['submit'])) {
        $del = isset($_POST['del']) ? (int) ($_POST['del']) : null;

        if ($del == 2 && $user->rights == 9) {
            // Удаляем топик
            $req1 = $db->query("SELECT * FROM `cms_forum_files` WHERE `topic` = '${id}'");

            if ($req1->rowCount()) {
                while ($res1 = $req1->fetch()) {
                    unlink(UPLOAD_PATH . 'forum/attach/' . $res1['filename']);
                }

                $db->exec("DELETE FROM `cms_forum_files` WHERE `topic` = '${id}'");
                $db->query('OPTIMIZE TABLE `cms_forum_files`');
            }

            $db->exec("DELETE FROM `forum_messages` WHERE `topic_id` = '${id}'");
            $db->exec("DELETE FROM `forum_topic` WHERE `id`='${id}'");
        } elseif ($del = 1) {
            // Скрываем топик
            $db->exec("UPDATE `forum_topic` SET `deleted` = '1', `deleted_by` = '" . $user->name . "' WHERE `id` = '${id}'");
            $db->exec("UPDATE `cms_forum_files` SET `del` = '1' WHERE `topic` = '${id}'");
        }
        header('Location: /forum/?type=topics&id=' . $res['section_id']);
        exit;
    }

    echo $view->render(
        'forum::delete_topic',
        [
            'title'      => _t('Delete Topic'),
            'page_title' => _t('Delete Topic'),
            'id'         => $id,
            'back_url'   => '/forum/?type=topic&id=' . $id,
        ]
    );
} else {
    http_response_code(403);
    echo $view->render(
        'system::pages/result',
        [
            'title'         => _t('Access forbidden'),
            'type'          => 'alert-danger',
            'message'       => _t('Access forbidden'),
            'back_url'      => '/forum/',
            'back_url_name' => _t('Back'),
        ]
    );
}
