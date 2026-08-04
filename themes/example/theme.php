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
    'name' => 'Example',

    // Everything this theme does not carry is taken from the default one — templates, layouts
    // and assets alike. That is why it can consist of a single file.
    'parent' => 'default',

    // No sources of its own, so no entry points: the pages load the bundle of the default theme.
    'entries' => [],

    'settings' => [],
];
