<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Checker;

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Connection;
use Johncms\Checker\Exceptions\DatabaseCheckException;

class DBChecker implements DatabaseProbeInterface
{
    protected ?Connection $connection;

    /** @var string */
    public const MARIADB_VERSION = '10.2';

    /** @var string */
    public const MYSQL_VERSION = '5.7';

    public function __construct(?Connection $connection = null)
    {
        $this->connection = $connection;
    }

    public function driver(): string
    {
        return $this->connection()->getDriverName();
    }

    /**
     * Getting information about the database server.
     *
     * @return array{version_raw: string, version_clean: string, server_name: string, required_version: string, error: bool}
     */
    public function versionInfo(): array
    {
        $res = $this->connection()->select('SELECT VERSION() as ver;');
        $version = $res[0]->ver ?? null;
        if (! is_string($version)) {
            throw new DatabaseCheckException('The database did not return a server version.');
        }
        $server = stripos($version, 'mariadb') !== false ? 'MariaDB' : 'MySQL';
        $reported = $server === 'MariaDB' ? preg_replace('/^5\.5\.5-/', '', $version) : $version;
        if (preg_match('/^\d+\.\d+(?:\.\d+)?/', $reported, $matches) !== 1) {
            throw new DatabaseCheckException('The database returned an unrecognised server version.');
        }
        $required = $this->getRequiredVersion($server);

        return [
            'version_raw' => $version,
            'version_clean' => $matches[0],
            'server_name' => $server,
            'required_version' => $required,
            'error' => version_compare($matches[0], $required, '<'),
        ];
    }

    protected function getRequiredVersion(string $server_name): string
    {
        if ($server_name === 'MariaDB') {
            return self::MARIADB_VERSION;
        }
        return self::MYSQL_VERSION;
    }

    /**
     * Checking whether the correct types are returned from the database.
     *
     * A number selected without a table tells as much about the driver as one read back from a
     * table, and asks for no right to create tables — nor commits the open transaction, which DDL
     * does in MySQL.
     */
    public function checkMysqlnd(): bool
    {
        $row = $this->connection()->selectOne('SELECT 666 AS test_int_col');

        return $row !== null && is_int($row->test_int_col);
    }

    private function connection(): Connection
    {
        return $this->connection ?? Manager::connection();
    }
}
