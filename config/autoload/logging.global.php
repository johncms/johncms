<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

return [
    'logging' => [
        // Default handler
        'default'  => 'file',

        // List of storages
        'handlers' => [
            'file' => [
                'path' => LOG_PATH . 'johncms.log',
                'days' => 10,
            ],
        ],
    ],
];
