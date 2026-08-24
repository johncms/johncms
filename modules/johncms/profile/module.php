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
    // everything else uses: @profile in a template, d__('profile', …), --source=profile.
    'key'   => 'johncms/profile',
    'alias' => 'profile',
    'name'  => 'Profile',

    // Modules whose services this one is built against: it cannot be loaded without them.
    'requires' => [
        'modules' => [
            'johncms/forum' => '^10.0',
            'johncms/guestbook' => '^10.0',
            'johncms/mail' => '^10.0',
        ],
    ],
];
