<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Checker\CheckProfile;
use Johncms\Checker\CheckStatus;
use Johncms\Checker\SystemCheckerInterface;
use Johncms\Http\Request;

/** @var Request $request Built by the installer entry point, which includes this file. */
/** @var \Psr\Container\ContainerInterface $container */

$viewData += ['title' => __('Checking parameters'), 'page_title' => __('Checking parameters')];

$report = $container->get(SystemCheckerInterface::class)->run(CheckProfile::Installer);
$check_extensions = [];
foreach ($report->sections as $section) {
    foreach ($section['checks'] as $check) {
        $check_extensions[] = [
            'name' => $check->name,
            'value' => $check->value,
            'error' => $check->status !== CheckStatus::Passed,
            'description' => $check->description,
        ];
    }
}

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

echo $view->render('@install/step-2.twig', $viewData + ['data' => $data]);
