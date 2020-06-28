<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Forum\Models\ForumFile;
use Forum\Models\ForumMessage;
use Illuminate\Support\Collection;
use Johncms\FileInfo;
use Johncms\System\Http\Request;
use Johncms\Users\User;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var array $config
 */

$extensions = new Collection(di('config')['forum']['extensions']);

/** @var Request $request */
$request = di(Request::class);

/** @var User $user */
$user = di(User::class);

if (! $id || ! $user->is_valid) {
    http_response_code(403);
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('Access forbidden'),
            'type'          => 'alert-danger',
            'message'       => __('Access forbidden'),
            'back_url'      => '/forum/',
            'back_url_name' => __('Back'),
        ]
    );
    exit;
}

// Проверяем, тот ли юзер заливает файл и в нужное ли место
$message = (new ForumMessage())->find($id);
if ($message === null || $message->user_id !== $user->id) {
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('Wrong data'),
            'type'          => 'alert-danger',
            'message'       => __('Wrong data'),
            'back_url'      => '/forum/',
            'back_url_name' => __('Back'),
        ]
    );
    exit;
}

// Проверяем лимит времени, отведенный для выгрузки файла
if ($message->date < (time() - 3600)) {
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('Add file'),
            'type'          => 'alert-danger',
            'message'       => __('The time allotted for the file upload has expired'),
            'back_url'      => '/forum/?&typ=topic&id=' . $message->topic_id . '&amp;page=' . $page,
            'back_url_name' => __('Back'),
        ]
    );
    exit;
}

if ($request->getMethod() === 'POST') {
    // Обработка файла (если есть), проверка на ошибки
    $files = $request->getUploadedFiles();
    if (! empty($files) && ! empty($files['fail'])) {
        /** @var GuzzleHttp\Psr7\UploadedFile $file */
        $file = $files['fail'];

        $file_info = new FileInfo($file->getClientFilename());
        $ext = strtolower($file_info->getExtension());

        $error = [];
        // Check file size
        if ($file->getSize() > 1024 * $config['flsz']) {
            $error[] = __('File size exceed') . ' ' . $config['flsz'] . 'kb.';
        }

        // Check allowed extensions
        // Список допустимых расширений файлов.
        $all_ext = $extensions->flatten();
        if ($all_ext->search($ext, true) === false) {
            $error[] = __('The forbidden file format.<br>You can upload files of the following extension') . ':<br>' . $all_ext->implode(', ');
        }

        $file_name = $file_info->getCleanName();

        // Проверка наличия файла с таким же именем
        if (file_exists(UPLOAD_PATH . 'forum/attach/' . $file_name)) {
            $file_name = time() . $file_name;
        }

        // Сохраняем файл
        if (! $error) {
            $file->moveTo(UPLOAD_PATH . 'forum/attach/' . $file_name);
            if (! $file->isMoved()) {
                $error[] = __('Error uploading file');
            }
        }

        if (! $error) {
            // Определяем тип файла
            if (in_array($ext, $extensions->get('windows'))) {
                $type = 1;
            } elseif (in_array($ext, $extensions->get('java'))) {
                $type = 2;
            } elseif (in_array($ext, $extensions->get('sis'))) {
                $type = 3;
            } elseif (in_array($ext, $extensions->get('documents'))) {
                $type = 4;
            } elseif (in_array($ext, $extensions->get('pictures'))) {
                $type = 5;
            } elseif (in_array($ext, $extensions->get('archives'))) {
                $type = 6;
            } elseif (in_array($ext, $extensions->get('video'))) {
                $type = 7;
            } elseif (in_array($ext, $extensions->get('audio'))) {
                $type = 8;
            } else {
                $type = 9;
            }

            // Заносим данные в базу
            $file = (new ForumFile());
            $file->create(
                [
                    'cat'      => $message->topic->section->parent,
                    'subcat'   => $message->topic->section_id,
                    'topic'    => $message->topic_id,
                    'post'     => $id,
                    'time'     => $message->date,
                    'filename' => $file_name,
                    'filetype' => $type,
                ]
            );
        } else {
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => __('Add file'),
                    'page_title'    => __('Error uploading file'),
                    'type'          => 'alert-danger',
                    'message'       => $error,
                    'back_url'      => '/forum/?act=addfile&id=' . $id,
                    'back_url_name' => __('Repeat'),
                ]
            );
            exit;
        }
    } else {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => __('Add file'),
                'page_title'    => __('Add file'),
                'type'          => 'alert-danger',
                'message'       => __('Error uploading file'),
                'back_url'      => '/forum/?act=addfile&id=' . $id,
                'back_url_name' => __('Repeat'),
            ]
        );
        exit;
    }
    $count_messages = (new ForumMessage())->where('topic_id', '=', $message->topic_id)->count();
    $page = ceil($count_messages / $user->set_user->kmess);
    $file_attached = true;
}

echo $view->render(
    'forum::add_file',
    [
        'title'         => __('Add File'),
        'page_title'    => __('Add File'),
        'id'            => $id,
        'file_attached' => $file_attached ?? false,
        'topic_id'      => $message->topic_id,
        'back_url'      => '?type=topic&id=' . $message->topic_id . '&amp;page=' . $page,
    ]
);
