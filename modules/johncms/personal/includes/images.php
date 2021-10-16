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

if (($user->id !== $user_data['id'] && $user->rights < 7) || $user_data['rights'] > $user->rights) {
    echo $view->render(
        'system::pages/result',
        [
            'title'   => $title,
            'type'    => 'alert-danger',
            'message' => __('You cannot edit profile of higher administration'),
        ]
    );
    exit;
}
/** @var ImageManager $image_manager */
$image_manager = di(ImageManager::class);

$data = [];
$error = [];

if ($mod === 'avatar') {
    // Выгружаем аватар
    $title = __('Upload Avatar');
    if ($request->getMethod() === 'POST') {
        $files = $request->getUploadedFiles();
        /** @var GuzzleHttp\Psr7\UploadedFile $file */
        $file = $files['imagefile'];
        if ($file->getSize() > 1024 * $config['flsz']) {
            $error[] = __('The weight of the file exceeds') . ' ' . $config['flsz'] . 'kb.';
        }

        if (empty($error)) {
            try {
                $avatar = UPLOAD_PATH . 'users/avatar/' . $user_data['id'] . '.png';
                $img = $image_manager->make($file->getStream());
                $img->resize(
                    150,
                    150,
                    static function ($constraint) {
                        /** @var $constraint Intervention\Image\Constraint */
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    }
                );
                $img->save($avatar, 100, 'png');
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'         => $title,
                        'type'          => 'alert-success',
                        'message'       => __('The avatar is successfully uploaded'),
                        'back_url'      => '?act=edit&amp;user=' . $user_data['id'],
                        'back_url_name' => __('Continue'),
                    ]
                );
                exit;
            } catch (Exception $exception) {
                $error[] = $exception->getMessage();
            }
        }
        echo $view->render(
            'system::pages/result',
            [
                'title'         => $title,
                'type'          => 'alert-danger',
                'message'       => $error,
                'back_url'      => '?act=images&amp;mod=avatar&amp;user=' . $user_data['id'],
                'back_url_name' => __('Repeat'),
            ]
        );
        exit;
    }
    $data['form_action'] = '?act=images&amp;mod=avatar&amp;user=' . $user_data['id'];
} else {
    $title = __('Upload Photo');
    if ($request->getMethod() === 'POST') {
        $files = $request->getUploadedFiles();
        /** @var GuzzleHttp\Psr7\UploadedFile $file */
        $file = $files['imagefile'];
        if ($file->getSize() > 1024 * $config['flsz']) {
            $error[] = __('The weight of the file exceeds') . ' ' . $config['flsz'] . 'kb.';
        }

        if (empty($error)) {
            try {
                $photo = UPLOAD_PATH . 'users/photo/' . $user_data['id'] . '.jpg';
                $small_photo = UPLOAD_PATH . 'users/photo/' . $user_data['id'] . '_small.jpg';
                $img = $image_manager->make($file->getStream());
                $img->resize(
                    1024,
                    960,
                    static function ($constraint) {
                        /** @var $constraint Intervention\Image\Constraint */
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    }
                );
                $img->save($photo, 100, 'jpg');

                $width = 400;
                $height = 300;
                // Создаем превью
                $resized = $image_manager->make($file->getStream())
                    ->resize(
                        $width,
                        $height,
                        static function ($constraint) {
                            /** @var $constraint Intervention\Image\Constraint */
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        }
                    )
                    ->save($small_photo, 100, 'jpg');

                echo $view->render(
                    'system::pages/result',
                    [
                        'title'         => $title,
                        'type'          => 'alert-success',
                        'message'       => __('The photo is successfully uploaded'),
                        'back_url'      => '?act=edit&amp;user=' . $user_data['id'],
                        'back_url_name' => __('Continue'),
                    ]
                );
                exit;
            } catch (Exception $exception) {
                $error[] = $exception->getMessage();
            }
        }
        echo $view->render(
            'system::pages/result',
            [
                'title'         => $title,
                'type'          => 'alert-danger',
                'message'       => $error,
                'back_url'      => '?act=images&amp;mod=up_photo&amp;user=' . $user_data['id'],
                'back_url_name' => __('Repeat'),
            ]
        );
        exit;
    }

    $data['form_action'] = '?act=images&amp;mod=up_photo&amp;user=' . $user_data['id'];
}

$nav_chain->add(($user_data['id'] !== $user->id ? __('Profile') : __('My Profile')), '?user=' . $user_data['id']);
$nav_chain->add($title);

$data['back_url'] = '?user=' . $user_data['id'];

echo $view->render(
    'profile::images',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
