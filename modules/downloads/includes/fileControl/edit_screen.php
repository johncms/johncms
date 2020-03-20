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
use Johncms\FileInfo;
use Intervention\Image\ImageManager;
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

if (! $req_down->rowCount()) {
    http_response_code(403);
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('Upload screenshot'),
            'type'          => 'alert-danger',
            'message'       => __('File not found'),
            'back_url'      => $urls['downloads'],
            'back_url_name' => __('Downloads'),
        ]
    );
    exit;
}

$screen = [];
if (
    isset($get['do'], $post['delete_token'], $_SESSION['delete_token']) &&
    $_SESSION['delete_token'] === $post['delete_token'] &&
    $request->getMethod() === 'POST'
) {
    $screens_dir = DOWNLOADS_SCR . $id . DIRECTORY_SEPARATOR;
    $file = new FileInfo($screens_dir . trim($get['do']));
    if ($file->isFile()) {
        unlink($screens_dir . $file->getFilename());
    }
    header('Location: ?act=edit_screen&id=' . $id);
    exit;
}

if ($request->getMethod() === 'POST') {
    // Загрузка скриншота
    $dir = DOWNLOADS_SCR . $id;
    if (! is_dir($dir) && ! mkdir($dir, 0777) && ! is_dir($dir)) {
        throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
    }

    $files = $request->getUploadedFiles();
    if (! empty($files) && ! empty($files['screen'])) {
        /** @var GuzzleHttp\Psr7\UploadedFile $screen */
        $screen = $files['screen'] ?? false;
        $file_name = $dir . '/' . $id . '.png';
        if (file_exists($file_name)) {
            $file_name = $dir . '/' . time() . '_' . $id . '.png';
        }
        // Пытаемся обработать файл и сохранить его
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
            $img->save($file_name, 100, 'png');
            $screen_attached = true;
        } catch (Exception $exception) {
            $screen_attached = false;
            $screen_attached_error = $exception->getMessage();
        }
    }

    if (isset($screen_attached) && $screen_attached) {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => __('Upload screenshot'),
                'type'          => 'alert-success',
                'message'       => __('Screenshot is attached'),
                'back_url'      => '?act=edit_screen&amp;id=' . $id,
                'back_url_name' => __('Back'),
            ]
        );
    } else {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => __('Upload screenshot'),
                'type'          => 'alert-danger',
                'message'       => __('Screenshot not attached') . ' ' . ($screen_attached_error ?? ''),
                'back_url'      => '?act=edit_screen&amp;id=' . $id,
                'back_url_name' => __('Repeat'),
            ]
        );
    }
} else {
    // Выводим скриншоты
    $screens = Screen::getScreens($id);
    $delete_token = uniqid('', true);
    $_SESSION['delete_token'] = $delete_token;
    echo $view->render(
        'downloads::edit_screen',
        [
            'title'        => htmlspecialchars($res_down['rus_name']),
            'page_title'   => htmlspecialchars($res_down['rus_name']),
            'id'           => $id,
            'screens'      => $screens,
            'urls'         => $urls,
            'delete_token' => $delete_token,
            'action_url'   => '?act=edit_screen&amp;id=' . $id,
            'extensions'   => implode(', ', $defaultExt),
        ]
    );
}
