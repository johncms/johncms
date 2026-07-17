<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Modules\Admin\Domain\Services\LanguageFilesManagerInterface;
use Johncms\System\Http\Request;

/** @var Request $request */
$request = di(Request::class);

/** @var LanguageFilesManagerInterface $languageFilesManager */
$languageFilesManager = di(LanguageFilesManagerInterface::class);

$view->addData(
    [
        'title'      => __('Preparing for installation'),
        'page_title' => 'JohnCMS ' . CMS_VERSION,
    ]
);

$request_locale = $request->getQuery('set_locale');

$lng_list = $languageFilesManager->getInstalled();

// If the user is changing language
if (! empty($request_locale) && array_key_exists($request_locale, $lng_list)) {
    $_SESSION['lng'] = $request_locale;
    header('Location: /install/');
    exit;
}

$data = [
    'lng_list' => $lng_list,
];

echo $view->render('install::step_1', ['data' => $data]);
