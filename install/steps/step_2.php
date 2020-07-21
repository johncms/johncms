<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\System\Http\Request;

/** @var Request $request */
$request = di(Request::class);

$view->addData(
    [
        'title'      => 'Проверка параметров',
        'page_title' => 'Проверка параметров',
    ]
);

$check_extensions = [
    [
        'name'        => 'Версия PHP',
        'value'       => PHP_VERSION,
        'error'       => (PHP_VERSION_ID < 70200),
        'description' => 'Версия PHP должна быть не ниже 7.2',
    ],
    [
        'name'        => 'PDO',
        'value'       => class_exists(PDO::class) ? 'Да' : 'Нет',
        'error'       => ! class_exists(PDO::class),
        'description' => 'Необходимо установить php расширение PDO',
    ],
    [
        'name'        => 'Расширение Imagick или GD',
        'value'       => (extension_loaded('gd') || extension_loaded('imagick')) ? 'Да' : 'Нет',
        'error'       => (! extension_loaded('gd') && ! extension_loaded('imagick')),
        'description' => 'Необходимо установить php расширение Imagick или GD',
    ],
    [
        'name'        => 'Расширение zlib',
        'value'       => extension_loaded('zlib') ? 'Да' : 'Нет',
        'error'       => ! extension_loaded('zlib'),
        'description' => 'Необходимо установить php расширение zlib',
    ],
    [
        'name'        => 'Расширение mbstring',
        'value'       => extension_loaded('mbstring') ? 'Да' : 'Нет',
        'error'       => ! extension_loaded('mbstring'),
        'description' => 'Необходимо установить php расширение mbstring',
    ],
];

$error_extensions = array_filter(
    $check_extensions,
    static function ($item) {
        return $item['error'];
    }
);

$folders = [
    'data/cache/',
    'upload/downloads/files/',
    'upload/downloads/screen/',
    'upload/forum/attach/',
    'upload/forum/topics/',
    'upload/library/',
    'upload/library/tmp',
    'upload/library/images',
    'upload/library/images/big',
    'upload/library/images/orig',
    'upload/library/images/small',
    'upload/users/album/',
    'upload/users/avatar/',
    'upload/users/photo/',
    'upload/mail/',
    'config/autoload/',
];

$folder_right_errors = [];
foreach ($folders as $val) {
    if (! is_writable(ROOT_PATH . $val)) {
        $folder_right_errors[] = $val;
    }
}

$data = [
    'next_step_disabled'  => (! empty($folder_right_errors) || ! empty($error_extensions)),
    'check_extensions'    => $check_extensions,
    'folder_right_errors' => $folder_right_errors,
];

echo $view->render('install::step_2', ['data' => $data]);
