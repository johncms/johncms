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

class DBChecker
{
    /** @var Connection */
    protected $connection;

    /** @var string */
    public const MARIADB_VERSION = '10.1.26';

    /** @var string */
    public const MYSQL_VERSION = '5.6.4';

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
        preg_match('/maria/', $version, $server_name_matches);
        if (! empty($matches[0])) {
            $server_name = 'MySQL';
            if ($server_name_matches[0] === 'maria') {
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
}
