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
use Johncms\FileInfo;
use Johncms\NavChain;
use Psr\Http\Message\ServerRequestInterface;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var array $config
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var NavChain $nav_chain
 * @var Johncms\System\Users\User $user
 * @var ServerRequestInterface $request
 */

$request = di(ServerRequestInterface::class);

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

$get = $request->getQueryParams();
$post = $request->getParsedBody();

$del = isset($get['del']) ? (int) $get['del'] : false;
$edit = isset($get['edit']) ? (int) $get['edit'] : false;
$base_file_name = htmlspecialchars($res_down['rus_name']);
$nav_chain->add($base_file_name, '?act=view&id=' . $id);
$nav_chain->add(__('Additional files'));
if ($edit) {
    // Изменяем файл
    $name_link = isset($post['name_link']) ? htmlspecialchars(mb_substr($post['name_link'], 0, 200)) : null;
    $req_file_more = $db->query("SELECT `rus_name` FROM `download__more` WHERE `id` = '${edit}' LIMIT 1");

    /** @noinspection NotOptimalIfConditionsInspection */
    if ($name_link && $request->getMethod() === 'POST' && $req_file_more->rowCount()) {
        $stmt = $db->prepare(
            '
            UPDATE `download__more` SET
            `rus_name` = ?
            WHERE `id` = ?
        '
        );

        $stmt->execute(
            [
                $name_link,
                $edit,
            ]
        );

        header('Location: ?act=files_more&id=' . $id);
    } else {
        $res_file_more = $req_file_more->fetch();
        echo $view->render(
            'downloads::edit_additional_form',
            [
                'title'      => __('Edit File'),
                'page_title' => htmlspecialchars($res_down['rus_name']),
                'id'         => $id,
                'urls'       => $urls,
                'file_name'  => htmlspecialchars($res_file_more['rus_name']),
                'action_url' => '?act=files_more&amp;id=' . $id . '&amp;edit=' . $edit,
                'back_url'   => '?act=files_more&amp;id=' . $id,
            ]
        );
        exit;
    }
} elseif ($del) {
    // Удаление файла
    $req_file_more = $db->query("SELECT `name` FROM `download__more` WHERE `id` = '${del}'");

    if (isset($get['yes'], $post['delete_token']) && $_SESSION['delete_token'] === $post['delete_token'] && $req_file_more->rowCount()) {
        $res_file_more = $req_file_more->fetch();

        if (is_file($res_down['dir'] . '/' . $res_file_more['name'])) {
            unlink($res_down['dir'] . '/' . $res_file_more['name']);
        }

        $db->exec("DELETE FROM `download__more` WHERE `id` = '${del}' LIMIT 1");
        header('Location: ?act=files_more&id=' . $id);
    } else {
        $delete_token = uniqid('', true);
        $_SESSION['delete_token'] = $delete_token;
        echo $view->render(
            'downloads::delete_additional',
            [
                'title'        => __('Edit File'),
                'page_title'   => htmlspecialchars($res_down['rus_name']),
                'id'           => $id,
                'urls'         => $urls,
                'delete_token' => $delete_token,
                'action_url'   => '?act=files_more&amp;id=' . $id . '&amp;del=' . $del . '&amp;yes',
                'back_url'     => '?act=files_more&amp;id=' . $id,
            ]
        );
        exit;
    }
} elseif ($request->getMethod() === 'POST') {
    // Выгружаем файл
    $error = [];
    $link_file = isset($post['link_file']) ? str_replace('./', '_', trim($post['link_file'])) : null;
    $do_file = false;

    $files = $request->getUploadedFiles();
    /** @var GuzzleHttp\Psr7\UploadedFile $uploaded_file */
    $uploaded_file = $files['fail'] ?? null;

    if ($link_file) {
        if (mb_strpos($link_file, 'http://') !== 0) {
            $error[] = __('Invalid Link');
        } else {
            $link_file = str_replace('http://', '', $link_file);

            if ($link_file) {
                $do_file = true;
                $fname = basename($link_file);
                $fsize = 0;
            } else {
                $error[] = __('Invalid Link');
            }
        }

        if ($error) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => __('Error'),
                    'type'          => 'alert-danger',
                    'message'       => $error,
                    'back_url'      => '?act=files_more&amp;id=' . $id,
                    'back_url_name' => __('Repeat'),
                ]
            );
            exit;
        }
    } elseif ($uploaded_file !== null) {
        $do_file = true;
        $fname = $uploaded_file->getClientFilename();
        $fsize = $uploaded_file->getSize();
    }

    if ($do_file) {
        $new_file = isset($post['new_file']) ? trim($post['new_file']) : null;
        $name_link = isset($post['name_link']) ? htmlspecialchars(mb_substr($post['name_link'], 0, 200)) : null;

        $file_name = new FileInfo($fname);
        $ext = strtolower($file_name->getExtension());

        if (! empty($new_file)) {
            $file_name = new FileInfo($new_file . '.' . $ext);
        }

        $fname = $file_name->getCleanName();

        if (empty($name_link)) {
            $error[] = __('The required fields are not filled');
        }

        if ($fsize > 1024 * $config['flsz'] && ! $link_file) {
            $error[] = __('The weight of the file exceeds') . ' ' . $config['flsz'] . 'kb.';
        }

        if (! in_array($ext, $defaultExt, true)) {
            $error[] = __('Prohibited file type!<br>To upload allowed files that have the following extensions') . ': ' . implode(', ', $defaultExt);
        }

        if (empty($error)) {
            $newFile = 'file' . $id . '_' . $fname;

            if (file_exists($res_down['dir'] . '/' . $newFile)) {
                $fname = 'file' . $id . '_' . time() . $fname;
            } else {
                $fname = $newFile;
            }

            if ($link_file) {
                $up_file = copy('http://' . $link_file, "{$res_down['dir']}/${fname}");
                $fsize = filesize("{$res_down['dir']}/${fname}");
            } else {
                $uploaded_file->moveTo($res_down['dir'] . '/' . $fname);
                $up_file = $uploaded_file->isMoved();
            }

            if ($up_file) {
                $stmt = $db->prepare(
                    '
                      INSERT INTO `download__more`
                      (`refid`, `time`, `name`, `rus_name`, `size`)
                      VALUES (?, ?, ?, ?, ?)
                    '
                );

                $stmt->execute(
                    [
                        $id,
                        time(),
                        $fname,
                        $name_link,
                        (int) $fsize,
                    ]
                );
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'         => __('File attached'),
                        'type'          => 'alert-success',
                        'message'       => __('File attached'),
                        'back_url'      => '?id=' . $id . '&amp;act=view',
                        'back_url_name' => __('Back'),
                    ]
                );
                exit;
            }
            $error[] = __('File not attached');
        }
    } else {
        $error[] = __('File not attached');
    }

    if (! empty($error)) {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => __('Error'),
                'type'          => 'alert-danger',
                'message'       => $error,
                'back_url'      => '?act=files_more&amp;id=' . $id,
                'back_url_name' => __('Repeat'),
            ]
        );
        exit;
    }
} else {
    // Дополнительные файлы
    $req_file_more = $db->query('SELECT * FROM `download__more` WHERE `refid` = ' . $id);
    $additional_files = [];
    while ($res_file_more = $req_file_more->fetch()) {
        $format = explode('.', $res_file_more['name']);
        $format_file = strtolower($format[count($format) - 1]);

        $res_file_more['rus_name'] = htmlspecialchars($res_file_more['rus_name']);
        $res_file_more['display_date'] = $tools->displayDate($res_file_more['time']);
        $res_file_more['display_size'] = Download::displayFileSize($res_file_more['size']);
        $res_file_more['edit_url'] = '?act=files_more&amp;id=' . $id . '&amp;edit=' . $res_file_more['id'];
        $res_file_more['delete_url'] = '?act=files_more&amp;id=' . $id . '&amp;del=' . $res_file_more['id'];

        $additional_files[] = $res_file_more;
    }

    echo $view->render(
        'downloads::files_more',
        [
            'title'            => htmlspecialchars($res_down['rus_name']),
            'page_title'       => htmlspecialchars($res_down['rus_name']),
            'id'               => $id,
            'additional_files' => $additional_files,
            'urls'             => $urls,
            'action_url'       => '?act=files_more&amp;id=' . $id,
            'extensions'       => implode(', ', $defaultExt),
        ]
    );
}
