<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

return [
    'translator' => [
        'translation_file_patterns' => [
            [
                'type'        => 'gettext',
                'base_dir'    => ROOT_PATH . 'system/locale',
                'pattern'     => '/%s/system.mo',
                'text_domain' => 'system',
            ],
        ],
    ],
];
