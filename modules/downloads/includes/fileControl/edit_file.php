<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Api\NavChainInterface;
use Psr\Http\Message\ServerRequestInterface;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Users\User $user
 * @var $urls
 * @var NavChainInterface $nav_chain
 * @var ServerRequestInterface $request
 */
$request = di(ServerRequestInterface::class);

if ($user->rights !== 4 && $user->rights < 6) {
    http_response_code(403);
    echo $view->render(
        'system::pages/result',
        [
            'title'         => _t('Edit File'),
            'type'          => 'alert-danger',
            'message'       => _t('Access denied'),
            'back_url'      => $urls['downloads'],
            'back_url_name' => _t('Downloads'),
        ]
    );
    exit;
}

$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();
if (! $req_down->rowCount() || ! is_file($res_down['dir'] . '/' . $res_down['name'])) {
    echo $view->render(
        'system::pages/result',
        [
            'title'         => _t('Edit File'),
            'type'          => 'alert-danger',
            'message'       => _t('File not found'),
            'back_url'      => $urls['downloads'],
            'back_url_name' => _t('Downloads'),
        ]
    );
    exit;
}

if ($request->getMethod() === 'POST') {
    $post = $request->getParsedBody();

    $name = isset($post['text']) ? trim($post['text']) : null;
    $name_link = isset($post['name_link']) ? htmlspecialchars(mb_substr($post['name_link'], 0, 200)) : null;

    if ($name_link && $name) {
        $stmt = $db->prepare(
            '
            UPDATE `download__files` SET
            `rus_name` = ?,
            `text`     = ?
            WHERE `id` = ?
        '
        );

        $stmt->execute(
            [
                $name,
                $name_link,
                $id,
            ]
        );

        header('Location: ?act=view&id=' . $id);
    } else {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => _t('Edit File'),
                'type'          => 'alert-danger',
                'message'       => _t('The required fields are not filled'),
                'back_url'      => '?act=edit_file&amp;id=' . $id,
                'back_url_name' => _t('Repeat'),
            ]
        );
    }
} else {
    $file_data = [
        'text'      => htmlspecialchars($res_down['rus_name']),
        'name_link' => htmlspecialchars($res_down['text']),
    ];
    echo $view->render(
        'downloads::edit_file_form',
        [
            'title'      => _t('Edit File'),
            'page_title' => _t('Edit File'),
            'id'         => $id,
            'urls'       => $urls,
            'file_data'  => $file_data,
            'action_url' => '?act=edit_file&amp;id=' . $id,
        ]
    );
}
