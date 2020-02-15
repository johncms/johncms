<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\FileInfo;
use Intervention\Image\ImageManager;
use Johncms\NavChain;
use Psr\Http\Message\ServerRequestInterface;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var array $config
 * @var PDO $db
 * @var Johncms\System\Users\User $user
 * @var $urls
 * @var NavChain $nav_chain
 * @var  ServerRequestInterface $request
 */

$request = di(ServerRequestInterface::class);

$req = $db->query("SELECT * FROM `download__category` WHERE `id` = '" . $id . "' LIMIT 1");
$res = $req->fetch();

if ($req->rowCount() && is_dir($res['dir'])) {
    if (($user->rights === 4 || $user->rights >= 6) || ($res['field'] && $user->isValid())) {
        $al_ext = $res['field'] ? explode(', ', $res['text']) : $defaultExt;

        if ($request->getMethod() === 'POST') {
            $load_cat = $res['dir'];
            $files = $request->getUploadedFiles();

            if (! empty($files) && ! empty($files['fail'])) {
                $error = [];
                // Request fields
                $post = $request->getParsedBody();

                $new_file = isset($post['new_file']) ? trim($post['new_file']) : null;
                $name = isset($post['text']) ? trim($post['text']) : null;
                $name_link = isset($post['name_link']) ? htmlspecialchars(mb_substr($post['name_link'], 0, 200)) : null;
                $text = isset($post['opis']) ? trim($post['opis']) : null;

                /** @var GuzzleHttp\Psr7\UploadedFile $file */
                $file = $files['fail'];

                $fname = $file->getClientFilename();
                $file_name = new FileInfo($fname);
                $ext = strtolower($file_name->getExtension());

                if (! empty($new_file)) {
                    $file_name = new FileInfo($new_file . '.' . $ext);
                }

                $fname = $file_name->getCleanName();

                if (empty($name)) {
                    $name = $fname;
                }

                if (empty($name_link)) {
                    $error[] = __('The required fields are not filled');
                }

                if ($file->getSize() > 1024 * $config['flsz']) {
                    $error[] = __('The weight of the file exceeds') . ' ' . $config['flsz'] . 'kb.';
                }

                if (! in_array($ext, $al_ext, true)) {
                    $error[] = __('Prohibited file type!<br>To upload allowed files that have the following extensions') . ': ' . implode(', ', $al_ext);
                }

                if ($error) {
                    echo $view->render(
                        'system::pages/result',
                        [
                            'title'         => __('Upload file'),
                            'type'          => 'alert-danger',
                            'message'       => $error,
                            'back_url'      => '?act=files_upload&amp;id=' . $id,
                            'back_url_name' => __('Repeat'),
                        ]
                    );
                } else {
                    if (file_exists($load_cat . '/' . $fname)) {
                        $fname = time() . $fname;
                    }

                    $file->moveTo($load_cat . '/' . $fname);
                    if ($file->isMoved()) {
                        if ($set_down['mod'] && ($user->rights < 6 && $user->rights != 4)) {
                            $moderation = true;
                            $type = 3;
                        } else {
                            $type = 2;
                        }
                        $stmt = $db->prepare(
                            "
                            INSERT INTO `download__files`
                            (`refid`, `dir`, `time`, `name`, `text`, `rus_name`, `type`, `user_id`, `about`, `desc`)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '')
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
                                $type,
                                $user->id,
                                $text,
                            ]
                        );
                        $file_id = $db->lastInsertId();

                        /** @var GuzzleHttp\Psr7\UploadedFile $screen */
                        $screen = $files['screen'] ?? false;
                        if ($screen) {
                            $screens_dir = DOWNLOADS_SCR . $file_id;
                            // Save screenshot
                            if (mkdir($screens_dir, 0777) || is_dir($screens_dir)) {
                                try {
                                    /** @var ImageManager $image_manager */
                                    $image_manager = di(ImageManager::class);
                                    $img = $image_manager->make($screen->getStream());

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

                        $urls['view_file_url'] = '';
                        if (! $set_down['mod'] || $user->rights > 6 || $user->rights === 4) {
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
                        }

                        echo $view->render(
                            'downloads::file_upload_result',
                            [
                                'title'                 => __('Upload File'),
                                'page_title'            => __('Upload File'),
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
                                'title'         => __('Upload file'),
                                'type'          => 'alert-danger',
                                'message'       => __('File not attached'),
                                'back_url'      => '?act=files_upload&amp;id=' . $id,
                                'back_url_name' => __('Repeat'),
                            ]
                        );
                    }
                }
            } else {
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'         => __('Upload file'),
                        'type'          => 'alert-danger',
                        'message'       => __('File not attached'),
                        'back_url'      => '?act=files_upload&amp;id=' . $id,
                        'back_url_name' => __('Repeat'),
                    ]
                );
            }
        } else {
            echo $view->render(
                'downloads::file_upload',
                [
                    'title'      => __('Upload File'),
                    'page_title' => __('Upload File'),
                    'id'         => $id,
                    'urls'       => $urls,
                    'action_url' => '?act=files_upload&amp;id=' . $id,
                    'extensions' => implode(', ', $al_ext),
                    'bbcode'     => di(Johncms\System\Legacy\Bbcode::class)->buttons('file_upload_form', 'desc'),
                ]
            );
        }
    } else {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => __('Upload file'),
                'type'          => 'alert-danger',
                'message'       => __('Access forbidden'),
                'back_url'      => '?id=' . $id,
                'back_url_name' => __('Back'),
            ]
        );
    }
} else {
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('Upload file'),
            'type'          => 'alert-danger',
            'message'       => __('The directory does not exist'),
            'back_url'      => $urls['downloads'],
            'back_url_name' => __('Back'),
        ]
    );
}
