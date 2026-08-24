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
    // everything else uses: @admin in a template, d__('admin', …), --source=admin.
    'key'   => 'johncms/admin',
    'alias' => 'admin',
    'name'  => 'Admin panel',

    // The panel the site is administered from: switching it off would leave nobody able
    // to switch it back on.
    'system' => true,
];
