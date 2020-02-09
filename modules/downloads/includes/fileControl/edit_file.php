<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Downloads\Download;
use Johncms\NavChain;
use Psr\Http\Message\ServerRequestInterface;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Users\User $user
 * @var $urls
 * @var NavChain $nav_chain
 * @var ServerRequestInterface $request
 */
$request = di(ServerRequestInterface::class);

$req_down = $db->query("SELECT * FROM `download__files` WHERE `id` = '" . $id . "' AND (`type` = 2 OR `type` = 3)  LIMIT 1");
$res_down = $req_down->fetch();
if (! $req_down->rowCount() || ! is_file($res_down['dir'] . '/' . $res_down['name'])) {
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('Edit File'),
            'type'          => 'alert-danger',
            'message'       => __('File not found'),
            'back_url'      => $urls['downloads'],
            'back_url_name' => __('Downloads'),
        ]
    );
    exit;
}

$audio_files = ['mp3', 'aac'];
$audio_tags = [];
$extension = strtolower(pathinfo($res_down['name'], PATHINFO_EXTENSION));
if (in_array($extension, $audio_files, true)) {
    $getID3 = new getID3();
    $getID3->encoding = 'cp1251';
    $getid = $getID3->analyze($res_down['dir'] . '/' . $res_down['name']);

    if (! empty($getid['tags']['id3v2'])) {
        $tagsArray = $getid['tags']['id3v2'];
    } elseif (! empty($getid['tags']['id3v1'])) {
        $tagsArray = $getid['tags']['id3v1'];
    }

    $tags_keys = ['artist', 'title', 'album', 'genre', 'year'];
    foreach ($tags_keys as $key) {
        $audio_tags[$key] = Download::mp3tagsOut($tagsArray[$key][0] ?? '');
    }
}

if ($request->getMethod() === 'POST') {
    $post = $request->getParsedBody();
    $name = isset($post['text']) ? trim($post['text']) : null;
    $desc = isset($post['desc']) ? trim($post['desc']) : null;
    $name_link = isset($post['name_link']) ? htmlspecialchars(mb_substr($post['name_link'], 0, 200)) : null;

    if ($name_link && $name) {
        $stmt = $db->prepare(
            '
            UPDATE `download__files` SET
            `rus_name` = ?,
            `text`     = ?,
            `about`    = ?
            WHERE `id` = ?
        '
        );

        $stmt->execute(
            [
                $name,
                $name_link,
                $desc,
                $id,
            ]
        );

        if (! empty($audio_tags) && ! empty($post['audio'])) {
            $save_tags = [];
            foreach ($audio_tags as $key => $tag) {
                $save_tags[$key][0] = Download::mp3tagsOut($post['audio'][$key] ?? '', 1);
            }
            $tagsWriter = new getid3_writetags();
            $tagsWriter->filename = $res_down['dir'] . '/' . $res_down['name'];
            $tagsWriter->tagformats = ['id3v1', 'id3v2.3'];
            $tagsWriter->tag_encoding = 'cp1251';
            $tagsWriter->tag_data = $save_tags;
            $tagsWriter->WriteTags();
        }

        header('Location: ?act=view&id=' . $id);
    } else {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => __('Edit File'),
                'type'          => 'alert-danger',
                'message'       => __('The required fields are not filled'),
                'back_url'      => '?act=edit_file&amp;id=' . $id,
                'back_url_name' => __('Repeat'),
            ]
        );
    }
} else {
    $file_data = [
        'text'      => htmlspecialchars($res_down['rus_name']),
        'name_link' => htmlspecialchars($res_down['text']),
        'desc'      => htmlentities($res_down['about'], ENT_QUOTES, 'UTF-8'),
    ];
    echo $view->render(
        'downloads::edit_file_form',
        [
            'title'      => __('Edit File'),
            'page_title' => __('Edit File'),
            'id'         => $id,
            'urls'       => $urls,
            'file_data'  => $file_data,
            'audio_tags' => $audio_tags,
            'action_url' => '?act=edit_file&amp;id=' . $id,
            'bbcode'     => di(Johncms\System\Legacy\Bbcode::class)->buttons('file_edit_form', 'desc'),
        ]
    );
}
