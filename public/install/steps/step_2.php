<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Http\Request;

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
        'error'       => (PHP_VERSION_ID < 80200),
        'description' => __('The PHP version must be at least %s', '8.2'),
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
    CACHE_PATH,
    LOG_PATH,
    UPLOAD_PATH . 'downloads/files/',
    UPLOAD_PATH . 'downloads/screen/',
    UPLOAD_PATH . 'forum/attach/',
    UPLOAD_PATH . 'forum/topics/',
    UPLOAD_PATH . 'library/',
    UPLOAD_PATH . 'library/tmp',
    UPLOAD_PATH . 'library/images',
    UPLOAD_PATH . 'library/images/big',
    UPLOAD_PATH . 'library/images/orig',
    UPLOAD_PATH . 'library/images/small',
    UPLOAD_PATH . 'users/album/',
    UPLOAD_PATH . 'users/avatar/',
    UPLOAD_PATH . 'users/photo/',
    UPLOAD_PATH . 'mail/',
    CONFIG_PATH . 'autoload/',
    // The sitemap and robots.txt are written here.
    PUBLIC_PATH,
];

$folder_right_errors = [];
foreach ($folders as $folder) {
    if (! is_writable($folder)) {
        // Show the path relative to the installation root.
        $folder_right_errors[] = str_replace(ROOT_PATH, '', $folder) ?: './';
    }
}

$data = [
    'next_step_disabled'  => (! empty($folder_right_errors) || ! empty($error_extensions)),
    'check_extensions'    => $check_extensions,
    'folder_right_errors' => $folder_right_errors,
];

echo $view->render('install::step_2', ['data' => $data]);
