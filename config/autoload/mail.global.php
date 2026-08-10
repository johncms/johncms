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
    'mail' => [
        // Default transport (can be sendmail, smtp)
        'transport' => 'sendmail',

        // Transport settings
        'options'   => [
            'smtp' => [
                'host'       => '127.0.0.1',
                'username'   => 'mail@example.com',
                'password'   => 'password',
                'port'       => 465,
                'encryption' => 'tls',
            ],
        ],
    ],
];
