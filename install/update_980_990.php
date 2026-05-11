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

$connection = Capsule::connection();

try {
    $connection->statement('ALTER TABLE `library_texts` ADD FULLTEXT `idx_name` (`name`)');
} catch (Throwable) {
}

echo 'The update was completed successfully';
