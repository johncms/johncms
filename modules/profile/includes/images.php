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

$foundUser = (array) $foundUser;

if (($user->id !== $foundUser['id'] && $user->rights < 7) || $foundUser['rights'] > $user->rights) {
    echo $view->render(
        'system::pages/result',
        [
            'title'   => $title,
            'type'    => 'alert-danger',
            'message' => _t('You cannot edit profile of higher administration'),
        ]
    );
    exit;
}

$image_manager = new ImageManager(['driver' => 'imagick']);

$data = [];
$error = [];

if ($mod === 'avatar') {
    // Выгружаем аватар
    $title = _t('Upload Avatar');
    if ($request->getMethod() === 'POST') {
        $files = $request->getUploadedFiles();
        /** @var GuzzleHttp\Psr7\UploadedFile $file */
        $file = $files['imagefile'];
        if ($file->getSize() > 1024 * $config['flsz']) {
            $error[] = _t('The weight of the file exceeds') . ' ' . $config['flsz'] . 'kb.';
        }

        if (empty($error)) {
            try {
                $avatar = UPLOAD_PATH . 'users/avatar/' . $foundUser['id'] . '.png';
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
                        'message'       => _t('The avatar is successfully uploaded'),
                        'back_url'      => '?act=edit&amp;user=' . $foundUser['id'],
                        'back_url_name' => _t('Continue'),
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
                'back_url'      => '?act=images&amp;mod=avatar&amp;user=' . $foundUser['id'],
                'back_url_name' => _t('Repeat'),
            ]
        );
        exit;
    }
    $data['form_action'] = '?act=images&amp;mod=avatar&amp;user=' . $foundUser['id'];
} else {
    $title = _t('Upload Photo');
    if ($request->getMethod() === 'POST') {
        $files = $request->getUploadedFiles();
        /** @var GuzzleHttp\Psr7\UploadedFile $file */
        $file = $files['imagefile'];
        if ($file->getSize() > 1024 * $config['flsz']) {
            $error[] = _t('The weight of the file exceeds') . ' ' . $config['flsz'] . 'kb.';
        }

        if (empty($error)) {
            try {
                $photo = UPLOAD_PATH . 'users/photo/' . $foundUser['id'] . '.jpg';
                $small_photo = UPLOAD_PATH . 'users/photo/' . $foundUser['id'] . '_small.jpg';
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
                        'message'       => _t('The photo is successfully uploaded'),
                        'back_url'      => '?act=edit&amp;user=' . $foundUser['id'],
                        'back_url_name' => _t('Continue'),
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
                'back_url'      => '?act=images&amp;mod=up_photo&amp;user=' . $foundUser['id'],
                'back_url_name' => _t('Repeat'),
            ]
        );
        exit;
    }

    $data['form_action'] = '?act=images&amp;mod=up_photo&amp;user=' . $foundUser['id'];
}

$nav_chain->add(($foundUser['id'] !== $user->id ? _t('Profile') : _t('My Profile')), '?user=' . $foundUser['id']);
$nav_chain->add($title);

$data['back_url'] = '?user=' . $foundUser['id'];

echo $view->render(
    'profile::images',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
