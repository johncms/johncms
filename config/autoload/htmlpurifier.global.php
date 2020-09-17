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
    'htmlpurifier' => [
        // Разрешенные классы
        'allowed_classes' => [
            'alert',
            'alert-info',
            'alert-success',
            'alert-warning',
            'alert-danger',
            'line-numbers',
            'language-php',
            'language-css',
            'language-javascript',
            'language-html',
            'language-sql',
            'language-xml',
            'media',
            'table',
            'image',
            'image-style-side',
        ],
    ],
];
