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

const CONSOLE_MODE = true;

require '../system/bootstrap.php';

if (! Capsule::schema()->hasTable('consents')) {
    echo 'The consent module is not installed. Run install_consent.php first';
    exit(1);
}

$column = Capsule::selectOne(
    'SELECT DATA_TYPE FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
    ['consents', 'title']
);

if ($column !== null && strtolower((string) $column->DATA_TYPE) === 'text') {
    echo 'The consents table is already up to date';
    exit(0);
}

// The title is shown next to the checkbox and may now contain inline HTML with links.
Capsule::statement('ALTER TABLE `consents` MODIFY `title` TEXT NOT NULL');

echo 'The update was completed successfully';
