<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Intervention\Image\ImageManagerStatic as Image;
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

$req = $db->query('SELECT * FROM `download__category` WHERE `id` = ' . $id);
$res = $req->fetch();

if (! $req->rowCount() || ! is_dir($res['dir'])) {
    http_response_code(404);
    echo $view->render(
        'system::pages/result',
        [
            'title'         => _t('Error'),
            'type'          => 'alert-danger',
            'message'       => _t('The directory does not exist'),
            'back_url'      => $urls['downloads'],
            'back_url_name' => _t('Downloads'),
        ]
    );
    exit;
}

$al_ext = $res['field'] ? explode(', ', $res['text']) : $defaultExt;

if ($request->getMethod() === 'POST') {
    $load_cat = $res['dir'];
    $error = [];
    $url = isset($post['fail']) ? str_replace('./', '_', trim($post['fail'])) : null;

    if ($url) {
        if (mb_strpos($url, 'http://') !== 0) {
            $error[] = _t('Invalid Link');
        } else {
            $url = str_replace('http://', '', $url);
        }
    }

    if ($url && ! $error) {
        $fname = basename($url);
        $new_file = isset($post['new_file']) ? trim($post['new_file']) : null;
        $name = isset($post['text']) ? trim($post['text']) : null;
        $name_link = isset($post['name_link']) ? htmlspecialchars(mb_substr($post['name_link'], 0, 200)) : null;
        $text = isset($post['opis']) ? trim($post['opis']) : null;
        $ext = explode('.', $fname);

        if (! empty($new_file)) {
            $fname = strtolower($new_file . '.' . $ext[1]);
            $ext = explode('.', $fname);
        }

        if (empty($name)) {
            $name = $fname;
        }

        if (empty($name_link)) {
            $error[] = _t('The required fields are not filled');
        }

        if (! in_array($ext[(count($ext) - 1)], $al_ext, true)) {
            $error[] = _t('Prohibited file type!<br>To upload allowed files that have the following extensions') . ': ' . implode(', ', $al_ext);
        }

        if (strlen($fname) > 100) {
            $error[] = _t('The file name length must not exceed 100 characters');
        }

        if (preg_match("/[^\da-zA-Z_\-.]+/", $fname)) {
            $error[] = _t('The file name contains invalid characters');
        }
    } elseif (! $url) {
        $error[] = _t('Invalid Link');
    }

    if ($error) {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => _t('File import'),
                'type'          => 'alert-danger',
                'message'       => $error,
                'back_url'      => '?act=import&amp;id=' . $id,
                'back_url_name' => _t('Repeat'),
            ]
        );
    } else {
        if (file_exists("${load_cat}/${fname}")) {
            $fname = time() . $fname;
        }

        if (copy('http://' . $url, "${load_cat}/${fname}")) {
            $stmt = $db->prepare(
                "
                    INSERT INTO `download__files`
                    (`refid`, `dir`, `time`, `name`, `text`, `rus_name`, `type`, `user_id`, `about`, `desc`)
                    VALUES (?, ?, ?, ?, ?, ?, 2, ?, ?, '')
                "
            );

            $stmt->execute(
                [
                    $id,
                    $load_cat,
                    time(),
                    $fname,
                    $name_link,
                    mb_substr($name, 0, 200),
                    $user->id,
                    $text,
                ]
            );
            $file_id = $db->lastInsertId();

            $files = $request->getUploadedFiles();

            /** @var GuzzleHttp\Psr7\UploadedFile $screen */
            $screen = $files['screen'] ?? null;
            if (empty($screen->getError())) {
                $screens_dir = DOWNLOADS_SCR . $file_id;
                // Save screenshot
                if (mkdir($screens_dir, 0777) || is_dir($screens_dir)) {
                    try {
                        Image::configure(['driver' => 'imagick']);
                        $img = Image::make($screen->getStream());

                        if ($set_down['screen_resize']) {
                            $img->resize(
                                1920,
                                1080,
                                static function ($constraint) {
                                    /** @var $constraint Intervention\Image\Constraint */
                                    $constraint->aspectRatio();
                                    $constraint->upsize();
                                }
                            );
                        }
                        $img->save($screens_dir . '/' . $file_id . '.png', 100, 'png');
                        $screen_attached = true;
                    } catch (Exception $exception) {
                        $screen_attached = false;
                        $screen_attached_error = $exception->getMessage();
                    }
                }
            }

            $urls['view_file_url'] = '?act=view&amp;id=' . $file_id;
            $dirid = $id;
            $sql = '';
            $i = 0;

            while ($dirid != '0' && $dirid != '') {
                $res_down = $db->query("SELECT `refid` FROM `download__category` WHERE `id` = '${dirid}' LIMIT 1")->fetch();
                if ($i) {
                    $sql .= ' OR ';
                }
                $sql .= '`id` = \'' . $dirid . '\'';
                $dirid = $res_down['refid'];
                ++$i;
            }

            $db->exec("UPDATE `download__category` SET `total` = (`total`+1) WHERE ${sql}");

            echo $view->render(
                'downloads::file_import_result',
                [
                    'title'                 => _t('File import'),
                    'page_title'            => _t('File import'),
                    'id'                    => $id,
                    'urls'                  => $urls,
                    'moderation'            => $moderation ?? null,
                    'screen_attached'       => $screen_attached ?? null,
                    'screen_attached_error' => $screen_attached_error ?? null,
                ]
            );
        } else {
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => _t('File import'),
                    'type'          => 'alert-danger',
                    'message'       => _t('File not attached'),
                    'back_url'      => '?act=import&amp;id=' . $id,
                    'back_url_name' => _t('Repeat'),
                ]
            );
        }
    }
} else {
    echo $view->render(
        'downloads::import',
        [
            'title'      => _t('File import'),
            'page_title' => _t('File import'),
            'id'         => $id,
            'urls'       => $urls,
            'action_url' => '?act=import&amp;id=' . $id,
            'extensions' => implode(', ', $al_ext),
            'bbcode'     => di(Johncms\System\Legacy\Bbcode::class)->buttons('file_import_form', 'desc'),
        ]
    );
}
