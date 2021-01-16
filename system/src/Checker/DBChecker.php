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
use Illuminate\Database\Schema\Blueprint;

class DBChecker
{
    /** @var Connection */
    protected $connection;

    /** @var string */
    public const MARIADB_VERSION = '10.2';

    /** @var string */
    public const MYSQL_VERSION = '5.7';

    public function __construct()
    {
        $this->connection = Manager::connection();
    }

    /**
     * Getting information about the database server.
     *
     * @return array
     */
    public function versionInfo(): array
    {
        $version_info = [];
        $res = $this->connection->select('SELECT VERSION() as ver;');
        $version = $res[0]->ver;
        preg_match('/\d*\.\d*\.\d*/', $version, $matches);
        preg_match('/maria/i', $version, $server_name_matches);
        if (! empty($matches[0])) {
            $server_name = 'MySQL';
            if (! empty($server_name_matches[0]) && strtolower($server_name_matches[0]) === 'maria') {
                $server_name = 'MariaDB';
            }
            $required_version = $this->getRequiredVersion($server_name);
            $version_info = [
                'version_raw'      => $version,
                'version_clean'    => $matches[0],
                'server_name'      => $server_name,
                'required_version' => $required_version,
                'error'            => version_compare($required_version, $version, '>'),
            ];
        }
        return $version_info;
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
     * @return bool
     */
    public function checkMysqlnd(): bool
    {
        $check_result = false;
        $tmp_table = 'mysqlnd_test_neceicegfbij';
        $schema = Manager::schema();
        $schema->dropIfExists($tmp_table);
        $schema->create(
            $tmp_table,
            static function (Blueprint $table) {
                $table->string('test_string_col');
                $table->integer('test_int_col');
            }
        );
        $this->connection->table($tmp_table)->insert(['test_string_col' => 'String value', 'test_int_col' => 666]);
        $row = $this->connection->table($tmp_table)->first();
        if ($row !== null && is_int($row->test_int_col)) {
            $check_result = true;
        }
        $schema->drop($tmp_table);
        return $check_result;
    }
}
