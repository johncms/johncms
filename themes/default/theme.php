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
    'name' => 'Default',

    // The root of every fallback chain: it carries a complete set of templates.
    'parent' => null,

    // Vite entry points. A theme that only overrides templates leaves them out and gets the
    // bundle of its parent.
    'entries' => [
        'public' => 'themes/default/src/js/app.js',
        'admin'  => 'themes/admin/src/js/app.js',
    ],

    // Values a template may read as app.theme.settings.*
    'settings' => [],
];
