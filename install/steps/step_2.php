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
        'title'      => __('Checking parameters'),
        'page_title' => __('Checking parameters'),
    ]
);

$check_extensions = [
    [
        'name'        => __('PHP version'),
        'value'       => PHP_VERSION,
        'error'       => (PHP_VERSION_ID < 70300),
        'description' => __('The PHP version must be at least %s', '7.3'),
    ],
    [
        'name'        => 'PDO',
        'value'       => class_exists(PDO::class) ? __('Yes') : __('No'),
        'error'       => ! class_exists(PDO::class),
        'description' => __('PHP extension PDO must be installed'),
    ],
    [
        'name'        => __('Imagick or GD extension'),
        'value'       => (extension_loaded('gd') || extension_loaded('imagick')) ? __('Yes') : __('No'),
        'error'       => (! extension_loaded('gd') && ! extension_loaded('imagick')),
        'description' => __('You must install the php extension Imagick or GD'),
    ],
    [
        'name'        => __('zlib extension'),
        'value'       => extension_loaded('zlib') ? __('Yes') : __('No'),
        'error'       => ! extension_loaded('zlib'),
        'description' => __('PHP extension zlib must be installed'),
    ],
    [
        'name'        => __('mbstring extension'),
        'value'       => extension_loaded('mbstring') ? __('Yes') : __('No'),
        'error'       => ! extension_loaded('mbstring'),
        'description' => __('PHP extension mbstring must be installed'),
    ],
    [
        'name'        => __('fileinfo extension'),
        'value'       => extension_loaded('fileinfo') ? __('Yes') : __('No'),
        'error'       => ! extension_loaded('fileinfo'),
        'description' => __('PHP extension fileinfo must be installed'),
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
