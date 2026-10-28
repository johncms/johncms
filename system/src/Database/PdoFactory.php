<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Database;

use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;
use PDO;

class PdoFactory
{
    public function __construct(
        private readonly Capsule $capsule,
    ) {
    }

    public function __invoke(): PDO
    {
        try {
            return $this->capsule->getDatabaseManager()->getPdo();
        } catch (Exception $exception) {
            echo '<b>Error connecting to the database server:</b><br>' . $exception->getMessage();

            // Not die() with the message: that ends the process with status 0, and a console
            // command or a test run that lost its database would report success.
            exit(1);
        }
    }
}
