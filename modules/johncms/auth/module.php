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
    // The key is the path on disk and the name of the package; the alias is the flat name
    // everything else uses: @auth in a template, d__('auth', …), --source=auth.
    'key'   => 'johncms/auth',
    'alias' => 'auth',
    'name'  => 'Authentication',

    // Modules whose services this one is built against: it cannot be loaded without them.
    'requires' => [
        'modules' => [
            'johncms/consent' => '^10.0',
        ],
    ],
];
