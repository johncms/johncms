<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Downloads\Screen;
use Psr\Http\Message\ServerRequestInterface;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Users\User $user
 * @var ServerRequestInterface $request
 */

$request = di(ServerRequestInterface::class);
$get = $request->getQueryParams();
$post = $request->getParsedBody();

$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();

if (! $req_down->rowCount() || ! is_file($res_down['dir'] . '/' . $res_down['name'])) {
    http_response_code(403);
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

if (
    isset($post['delete_token'], $_SESSION['delete_token']) &&
    $_SESSION['delete_token'] === $post['delete_token'] &&
    $request->getMethod() === 'POST'
) {
    $screens = Screen::getScreens($id);
    foreach ($screens as $screen) {
        @unlink($screen['path']);
    }
    @rmdir(DOWNLOADS_SCR . $id);

    $req_file_more = $db->query('SELECT * FROM `download__more` WHERE `refid` = ' . $id);

    if ($req_file_more->rowCount()) {
        while ($res_file_more = $req_file_more->fetch()) {
            if (is_file($res_down['dir'] . '/' . $res_file_more['name'])) {
                @unlink($res_down['dir'] . '/' . $res_file_more['name']);
            }
        }

        $db->exec('DELETE FROM `download__more` WHERE `refid` = ' . $id);
    }

    $db->exec('DELETE FROM `download__bookmark` WHERE `file_id` = ' . $id);
    $db->exec('DELETE FROM `download__comments` WHERE `sub_id` = ' . $id);
    @unlink($res_down['dir'] . '/' . $res_down['name']);
    $dirid = $res_down['refid'];
    $sql = '';
    $i = 0;

    while ($dirid != '0' && $dirid != '') {
        $res = $db->query("SELECT `refid` FROM `download__category` WHERE `id` = '${dirid}' LIMIT 1")->fetch();
        if ($i) {
            $sql .= ' OR ';
        }
        $sql .= '`id` = \'' . $dirid . '\'';
        $dirid = $res['refid'];
        ++$i;
    }

    $db->exec("UPDATE `download__category` SET `total` = (`total`-1) WHERE ${sql}");
    $db->exec('DELETE FROM `download__files` WHERE `id` = ' . $id);
    $db->query('OPTIMIZE TABLE `download__files`');
    header('Location: ?id=' . $res_down['refid']);
    exit;
}

$delete_token = uniqid('', true);
$_SESSION['delete_token'] = $delete_token;

echo $view->render(
    'downloads::delete_file',
    [
        'title'        => htmlspecialchars($res_down['rus_name']),
        'page_title'   => htmlspecialchars($res_down['rus_name']),
        'id'           => $id,
        'urls'         => $urls,
        'delete_token' => $delete_token,
        'action_url'   => '?act=delete_file&amp;id=' . $id,
        'back_url'     => '?act=view&amp;id=' . $id,
    ]
);
