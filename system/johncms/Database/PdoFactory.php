<?php
/*
 * mobiCMS Content Management System (http://mobicms.net)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

namespace Johncms\Database;

use Interop\Container\ContainerInterface;

class PdoFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');

        $dbHost = isset($config['pdo']['db_host']) ? $config['pdo']['db_host'] : 'localhost';
        $dbUser = isset($config['pdo']['db_user']) ? $config['pdo']['db_user'] : 'root';
        $dbPass = isset($config['pdo']['db_pass']) ? $config['pdo']['db_pass'] : '';
        $dbName = isset($config['pdo']['db_name']) ? $config['pdo']['db_name'] : 'johncms';

        try {
            $pdo = new \PDO('mysql:host=' . $dbHost . ';dbname=' . $dbName, $dbUser, $dbPass,
                [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
                    \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,
                ]
            );
        } catch (\PDOException $e) {
            throw new \RuntimeException($e->getMessage());
        }

        return $pdo;
    }
}
