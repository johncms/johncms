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

if (Capsule::schema()->hasTable('collections')) {
    echo 'The collections module is already installed';
    exit(0);
}

$installer = new ModuleInstaller('collections');
$installer->install();

// Demo data is optional: pass --demo to create the sample "Blog" collection.
if (in_array('--demo', $argv ?? [], true)) {
    $installer->installDemoData();
}

echo 'The update was completed successfully';
