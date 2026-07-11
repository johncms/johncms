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
use Johncms\Modules\ModuleInstaller;

const CONSOLE_MODE = true;

require '../system/bootstrap.php';

if (Capsule::schema()->hasTable('consents')) {
    echo 'The consent module is already installed';
    exit(0);
}

$installer = new ModuleInstaller('consent');
$installer->install();

echo 'The update was completed successfully';
