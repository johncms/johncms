<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

return [
    'providers'  => [],
    'middleware' => [
        \Johncms\Online\Middleware\UserStatMiddleware::class,
    ],
];
