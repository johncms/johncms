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
    'modules' => [
        /*
         * The modules this release ships with. A site installs them all and may switch some off
         * afterwards; what it has done is recorded in modules.local.php, which this file knows
         * nothing about.
         *
         * Which of them may not be switched off at all is a property of the module — the "system"
         * field of its module.php — and is not repeated here.
         */
        'bundled' => [
            'johncms/admin',
            'johncms/album',
            'johncms/auth',
            'johncms/collections',
            'johncms/community',
            'johncms/consent',
            'johncms/contacts',
            'johncms/downloads',
            'johncms/forum',
            'johncms/guestbook',
            'johncms/help',
            'johncms/homepage',
            'johncms/language',
            'johncms/library',
            'johncms/mail',
            'johncms/news',
            'johncms/notifications',
            'johncms/online',
            'johncms/profile',
            'johncms/redirect',
        ],
    ],
];
