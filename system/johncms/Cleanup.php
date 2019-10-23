<?php

declare(strict_types=1);

/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

namespace Johncms;

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
        $this->pdo->query('LOCK TABLE `' . $table . '` WRITE');
        $this->pdo->query('DELETE FROM `' . $table . '` WHERE `' . $timestampField . '` < ' . $condition);
        $this->pdo->query('OPTIMIZE TABLE `' . $table . '`');
        $this->pdo->query('UNLOCK TABLES');
    }
}
