<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Laminas\Mail\Transport\File as FileTransport;

return [
    'mail' => [
        // Default transport (can be sendmail, smtp, file or memory)
        'transport' => 'sendmail',

        // Transport settings
        'options'   => [
            'smtp' => [
                'name'              => 'localhost.localdomain',
                'host'              => '127.0.0.1',
                'connection_class'  => 'plain',
                'connection_config' => [
                    'username' => 'user',
                    'password' => 'pass',
                ],
            ],
            'file' => [
                'path'     => DATA_PATH . 'mail/',
                'callback' => static function (FileTransport $transport) {
                    return 'Message_' . microtime(true) . '_' . mt_rand() . '.txt';
                },
            ],
        ],
    ],
];
