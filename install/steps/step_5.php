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
        'title'      => 'Завершение установки',
        'page_title' => 'Завершение установки',
    ]
);


$data = [
    'errors'             => $errors ?? [],
    'fields'             => $fields ?? [],
    'next_step_disabled' => false,
];

echo $view->render('install::step_5', ['data' => $data]);
