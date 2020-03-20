<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Intervention\Image\ImageManager;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 * @var Johncms\System\Http\Request $request
 * @var Johncms\NavChain $nav_chain
 */

$config = di('config')['johncms'];

$data = [];
$title = __('Upload image');
// Выгрузка фотографии
if (($al && $foundUser['id'] === $user->id && empty($user->ban)) || $user->rights >= 7) {
    $nav_chain->add($title);
    $req_a = $db->query("SELECT * FROM `cms_album_cat` WHERE `id` = '${al}' AND `user_id` = " . $foundUser['id']);
    if (! $req_a->rowCount()) {
        // Если альбома не существует, завершаем скрипт
        echo $view->render(
            'system::pages/result',
            [
                'title'    => $title,
                'type'     => 'alert-danger',
                'message'  => __('Wrong data'),
                'back_url' => '/album/',
            ]
        );
        exit;
    }

    $res_a = $req_a->fetch();
    if ($request->getMethod() === 'POST') {
        /** @var ImageManager $image_manager */
        $image_manager = di(ImageManager::class);

        $files = $request->getUploadedFiles();
        /** @var GuzzleHttp\Psr7\UploadedFile $file */
        $file = $files['imagefile'];

        if ($file->getSize() > 1024 * $config['flsz']) {
            $error[] = __('The weight of the file exceeds') . ' ' . $config['flsz'] . 'kb.';
        }

        $dir = UPLOAD_PATH . 'users/album/' . $foundUser['id'] . '/';
        $original_file = 'img_' . time() . '.jpg';
        $tmb_file = 'tmb_' . time() . '.jpg';

        $image_uploaded = false;
        if (empty($error) && (is_dir($dir) || mkdir($dir, 0777) || is_dir($dir))) {
            try {
                // Сохраняем оригинал
                $img = $image_manager->make($file->getStream());
                $img->resize(
                    1920,
                    1080,
                    static function ($constraint) {
                        /** @var $constraint Intervention\Image\Constraint */
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    }
                );

                $img->save($dir . '/' . $original_file, 100, 'jpg');

                $width = 400;
                $height = 300;
                // Создаем превью
                $resized = $image_manager->make($file->getStream())->resize(
                    $width,
                    $height,
                    static function ($constraint) {
                        /** @var $constraint Intervention\Image\Constraint */
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    }
                );

                // Создаем подложку для картинки и помещаем в неё сжатую до нужных размеров копию
                $bg = $img->fit($width, $height)
                    ->blur(20)
                    ->insert($resized, 'center');

                $bg->save($dir . '/' . $tmb_file, 100, 'jpg');

                $description = $request->getPost('description', '');
                $description = mb_substr($description, 0, 1500);

                $db->prepare(
                    '
                      INSERT INTO `cms_album_files` SET
                      `album_id` = ?,
                      `user_id` = ?,
                      `img_name` = ?,
                      `tmb_name` = ?,
                      `description` = ?,
                      `time` = ?,
                      `access` = ?
                    '
                )->execute(
                    [
                        $al,
                        $foundUser['id'],
                        $original_file,
                        $tmb_file,
                        $description,
                        time(),
                        $res_a['access'],
                    ]
                );

                echo $view->render(
                    'system::pages/result',
                    [
                        'title'         => $title,
                        'type'          => 'alert-success',
                        'message'       => __('Image uploaded'),
                        'back_url'      => './show?al=' . $al . '&amp;user=' . $foundUser['id'],
                        'back_url_name' => __('Continue'),
                    ]
                );
                exit;
            } catch (Exception $exception) {
                $error[] = $exception->getMessage();
            }
        }
    }
    $data['action_url'] = './image_upload?al=' . $al . '&amp;user=' . $foundUser['id'];
    $data['back_url'] = './show?al=' . $al . '&amp;user=' . $foundUser['id'];
    $data['error_message'] = $error ?? [];
    echo $view->render(
        'album::add_photo',
        [
            'title'      => $title,
            'page_title' => $title,
            'data'       => $data,
        ]
    );
} else {
    // У пользователя не достаточно прав - завершаем скрипт
    echo $view->render(
        'system::pages/result',
        [
            'title'    => $title,
            'type'     => 'alert-danger',
            'message'  => __('Wrong data'),
            'back_url' => '/album/',
        ]
    );
    exit;
}
