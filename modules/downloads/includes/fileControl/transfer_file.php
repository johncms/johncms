<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Users\User $user
 * @var ServerRequestInterface $request
 */

$request = di(ServerRequestInterface::class);
$get = $request->getQueryParams();

$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();

if (! $req_down->rowCount() || ! is_file($res_down['dir'] . '/' . $res_down['name'])) {
    http_response_code(404);
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('File not found'),
            'type'          => 'alert-danger',
            'message'       => __('File not found'),
            'back_url'      => $urls['downloads'],
            'back_url_name' => __('Downloads'),
        ]
    );
    exit;
}

$do = isset($get['do']) ? trim($get['do']) : '';

if ($user->rights > 6) {
    $catId = isset($get['catId']) ? (int) ($get['catId']) : 0;

    if ($catId) {
        $queryDir = $db->query("SELECT * FROM `download__category` WHERE `id` = '${catId}' LIMIT 1");

        if (! $queryDir->rowCount()) {
            $catId = 0;
        }
    }

    if ($do === 'transfer' && ! empty($catId)) {
        if ($catId === $res_down['refid']) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => __('Move File'),
                    'type'          => 'alert-info',
                    'message'       => __('This is the current directory'),
                    'back_url'      => '?act=transfer_file&amp;id=' . $id . '&amp;catId=' . $catId,
                    'back_url_name' => __('Back'),
                ]
            );
            exit;
        }

        if (isset($get['yes'])) {
            $resDir = $queryDir->fetch();
            $req_file_more = $db->query("SELECT * FROM `download__more` WHERE `refid` = '" . $id . "'");

            if ($req_file_more->rowCount()) {
                while ($res_file_more = $req_file_more->fetch()) {
                    copy(
                        $res_down['dir'] . '/' . $res_file_more['name'],
                        $resDir['dir'] . '/' . $res_file_more['name']
                    );
                    unlink($res_down['dir'] . '/' . $res_file_more['name']);
                }
            }

            $name = $res_down['name'];
            $newFile = $resDir['dir'] . '/' . $res_down['name'];

            if (is_file($newFile)) {
                $name = time() . '_' . $res_down['name'];
                $newFile = $resDir['dir'] . '/' . $name;
            }

            copy($res_down['dir'] . '/' . $res_down['name'], $newFile);
            unlink($res_down['dir'] . '/' . $res_down['name']);

            $stmt = $db->prepare(
                '
                        UPDATE `download__files` SET
                        `name`     = ?,
                        `dir`      = ?,
                        `refid`    = ?
                        WHERE `id` = ?
                    '
            );

            $stmt->execute(
                [
                    $name,
                    $resDir['dir'],
                    $catId,
                    $id,
                ]
            );

            echo $view->render(
                'system::pages/result',
                [
                    'title'         => __('Move File'),
                    'type'          => 'alert-success',
                    'message'       => __('The file has been moved'),
                    'back_url'      => '?act=recount',
                    'back_url_name' => __('Update counters'),
                ]
            );
        } else {
            echo $view->render(
                'downloads::move_file_confirm',
                [
                    'title'      => htmlspecialchars($res_down['rus_name']),
                    'page_title' => htmlspecialchars($res_down['rus_name']),
                    'id'         => $id,
                    'urls'       => $urls,
                    'action_url' => '?act=transfer_file&amp;id=' . $id . '&amp;catId=' . $catId . '&amp;do=transfer&amp;yes',
                    'back_url'   => '?act=view&amp;id=' . $id,
                ]
            );
        }
    } else {
        $queryCat = $db->query("SELECT * FROM `download__category` WHERE `refid` = '${catId}'");
        $sections = [];
        while ($resCat = $queryCat->fetch()) {
            $resCat['rus_name'] = htmlspecialchars($resCat['rus_name']);
            $resCat['section_open_url'] = '?act=transfer_file&amp;id=' . $id . '&amp;catId=' . $resCat['id'];
            $resCat['section_move_url'] = '';
            if ($resCat['id'] !== $res_down['refid']) {
                $resCat['section_move_url'] = '?act=transfer_file&amp;id=' . $id . '&amp;catId=' . $resCat['id'] . '&amp;do=transfer';
            }
            $sections[] = $resCat;
        }
        $urls['move_to_current_url'] = '';
        if ($catId && $catId !== $res_down['refid']) {
            $urls['move_to_current_url'] = '?act=transfer_file&amp;id=' . $id . '&amp;catId=' . $catId . '&amp;do=transfer';
        }

        echo $view->render(
            'downloads::move_file',
            [
                'title'         => __('Move File'),
                'page_title'    => __('Move File'),
                'type'          => 'alert-success',
                'urls'          => $urls,
                'sections'      => $sections,
                'back_url'      => '?act=view&amp;id=' . $id,
                'back_url_name' => __('Back'),
            ]
        );
    }
}
