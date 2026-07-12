<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

const CONSOLE_MODE = true;

require '../system/bootstrap.php';

if (Capsule::schema()->hasColumn('cms_counters', 'require_cookie_consent')) {
    echo 'The counters table is already up to date';
    exit(0);
}

Capsule::schema()->table('cms_counters', static function (Blueprint $table): void {
    $table->boolean('require_cookie_consent')->default(0);
});

echo 'The update was completed successfully';
