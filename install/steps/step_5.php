<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

$view->addData(
    [
        'title'      => __('Complete the installation'),
        'page_title' => __('Complete the installation'),
    ]
);

echo $view->render('install::step_5');
