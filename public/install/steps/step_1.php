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
use Johncms\Http\Request;

/** @var Request $request Built by the installer entry point, which includes this file. */

/** @var LanguageFilesManagerInterface $languageFilesManager */
$languageFilesManager = di(LanguageFilesManagerInterface::class);

$viewData += ['title' => __('Preparing for installation'), 'page_title' => 'JohnCMS ' . CMS_VERSION];

$request_locale = $request->queryParam('set_locale');

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

echo $view->render('@install/step-1.twig', $viewData + ['data' => $data]);
