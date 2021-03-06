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
    'filesystem' => [
        // Default storage
        'default'  => 'local',

        // List of storages
        'storages' => [
            'local' => [
                'type'     => 'local',
                'root_dir' => UPLOAD_PATH,
            ],
        ],
    ],
];
