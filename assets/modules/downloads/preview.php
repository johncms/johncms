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

if (! isset($_GET['img'])) {
    exit;
}

require '../../../system/vendor/autoload.php';

$width = 220;
$height = 300;

$copyright = '';
$type = isset($_GET['type']) ? (int) $_GET['type'] : 0;
$image = htmlspecialchars(rawurldecode($_GET['img']));
$image = '../../../' . strtr($image, ['../' => '', '//' => '/', './' => '_',]);

if ($image && file_exists($image)) {
    $att_ext = strtolower(pathinfo($image, PATHINFO_EXTENSION));
    $pic_ext = [
        'gif',
        'jpg',
        'jpeg',
        'png',
    ];

    if (in_array($att_ext, $pic_ext, true)) {
        /** @var ImageManager $manager */
        $manager = di(ImageManager::class);
        $resized = $manager->make($image)
            ->resize(
                $width,
                $height,
                function ($constraint) {
                    /** @var $constraint Intervention\Image\Constraint */
                    $constraint->aspectRatio();
                    $constraint->upsize();
                }
            );
        $bg = $manager->make($image)
            ->fit($width, $height)
            ->blur(20)
            ->insert($resized, 'center');

        echo $bg->response('jpg', 100);
    }
}
