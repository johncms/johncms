<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

/**
 * In the excepts section you can define paths to exclude from the csrf check
 * You can use regular expressions, full paths or next simplified example:
 * /test-path/*
 * /test-path/next-path/
 */
return [
    'csrf' => [
        'excepts' => [],
    ],
];
