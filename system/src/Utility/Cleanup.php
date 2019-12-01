<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Utility;

use PDO;

class Cleanup
{
    /**
     * @var PDO
     */
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        // Очищаем таблицу `cms_sessions`
        $this->cleanupTable('cms_sessions', 'lastdate', time() - 86400);

        // Очищаем таблицу `cms_users_iphistory`
        $this->cleanupTable('cms_users_iphistory', 'time', time() - 7776000);
    }

    private function cleanupTable(string $table, string $timestampField, int $condition): void
    {
        $this->pdo->exec('LOCK TABLE `' . $table . '` WRITE');
        $this->pdo->query('DELETE FROM `' . $table . '` WHERE `' . $timestampField . '` < ' . $condition);
        $this->pdo->query('OPTIMIZE TABLE `' . $table . '`');
        $this->pdo->exec('UNLOCK TABLES');
    }
}
