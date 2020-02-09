<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Library\Hashtags;
use Library\Utils;
use Psr\Http\Message\ServerRequestInterface;

defined('_IN_JOHNCMS') || die('Error: restricted access');

$request = di(ServerRequestInterface::class);

/**
 * @var PDO $db
 * @var Johncms\System\Users\User $user
 * @var Johncms\System\View\Render $view
 * @var  ServerRequestInterface $request
 */

$title = __('Write Article');
$nav_chain->add($title);

if ($adm || ((isset($id) && $user->isValid()) && ($db->query('SELECT `user_add` FROM `library_cats` WHERE `id` = ' . $id)->rowCount()))) {
    $err = [];
    $name = isset($_POST['name']) ? mb_substr(trim($_POST['name']), 0, 100) : '';
    $announce = isset($_POST['announce']) ? mb_substr(trim($_POST['announce']), 0, 500) : '';
    $text = isset($_POST['text']) ? trim($_POST['text']) : '';
    $tag = isset($_POST['tags']) ? trim($_POST['tags']) : '';
    $md = false;
    $cid = false;

    $flood = $tools->antiflood();

    if ($flood) {
        $err[] = sprintf(__('You cannot add the Article so often<br>Please, wait %d sec.'), $flood);
    } elseif (isset($_POST['submit'])) {
        if (empty($name)) {
            $err[] = __('You have not entered the name');
        }
        if (! empty($_FILES['textfile']['name'])) {
            $ext = explode('.', $_FILES['textfile']['name']);
            if (mb_strtolower(end($ext)) === 'txt') {
                $newname = $_FILES['textfile']['name'];
                if (move_uploaded_file($_FILES['textfile']['tmp_name'], UPLOAD_PATH . 'library/tmp/' . $newname)) {
                    $txt = file_get_contents(UPLOAD_PATH . 'library/tmp/' . $newname);
                    if (mb_check_encoding($txt, 'windows-1251')) {
                        $txt = iconv('windows-1251', 'UTF-8', $txt);
                    } elseif (mb_check_encoding($txt, 'KOI8-R')) {
                        $txt = iconv('KOI8-R', 'UTF-8', $txt);
                    } else {
                        $err[] = __('The file is invalid encoding, preferably UTF-8');
                    }
                    $text = trim($txt);
                    unlink(UPLOAD_PATH . 'library/tmp' . DS . $newname);
                } else {
                    $err[] = __('Error uploading');
                }
            } else {
                $err[] = __('Invalid file format allowed * .txt');
            }
        } elseif (empty($text)) {
            $err[] = __('You have not entered text');
        }

        $md = $adm ? 1 : 0;

        if (! count($err)) {
            $insert = [
                $id,
                $name,
                $announce,
                $text,
                $user->name,
                $user->id,
                $md,
                (isset($_POST['comments']) ? 1 : 0),
                time(),
            ];
            $sql = '
                  INSERT INTO `library_texts`
                  SET
                    `cat_id` = ?,
                    `name` = ?,
                    `announce` = ?,
                    `text` = ?,
                    `uploader` = ?,
                    `uploader_id` = ?,
                    `premod` = ?,
                    `comments` = ?,
                    `time` = ?
                ';

            if ($db->prepare($sql)->execute($insert)) {
                $cid = (int) $db->lastInsertId();

                $files = $request->getUploadedFiles();
                /** @var GuzzleHttp\Psr7\UploadedFile $screen */
                $screen = $files['image'] ?? false;

                if ($screen->getClientFilename()) {
                    try {
                        Utils::imageUpload($cid, $screen);
                    } catch (Exception $exception) {
                        $err[] = __('Photo uploading error');
                    }
                }

                if (! empty($_POST['tags'])) {
                    $tags = (array) array_map('trim', explode(',', $_POST['tags']));
                    if (count($tags)) {
                        $obj = new Hashtags($cid);
                        $obj->addTags($tags);
                        $obj->delCache();
                    }
                }
                $db->exec('UPDATE `users` SET `lastpost` = ' . time() . ' WHERE `id` = ' . $user->id);
            }
        }
    }
    if (count($err)) {
        $error = '';
        foreach ($err as $e) {
            $error .= $tools->displayError($e);
        }
    }
} else {
    Utils::redir404();
}

echo $view->render(
    'library::addnew',
    [
        'title'      => $title,
        'page_title' => $title,
        'error'      => $error,
        'md'         => $md,
        'cid'        => $cid,
        'id'         => $id,
        'name'       => $name,
        'announce'   => $announce,
        'text'       => $text,
        'tag'        => $tag,
        'bbcode'     => di(Johncms\System\Legacy\Bbcode::class)->buttons('form', 'text'),
    ]
);
