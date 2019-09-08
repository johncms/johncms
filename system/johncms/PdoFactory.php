<?php
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

use Psr\Container\ContainerInterface;

class PdoFactory
{
    /**
     * @param ContainerInterface $container
     * @return \PDO
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config')['pdo'];

        $dbHost = isset($config['db_host']) ? $config['db_host'] : 'localhost';
        $dbUser = isset($config['db_user']) ? $config['db_user'] : 'root';
        $dbPass = isset($config['db_pass']) ? $config['db_pass'] : '';
        $dbName = isset($config['db_name']) ? $config['db_name'] : 'johncms';

        try {
            $pdo = new \PDO('mysql:host=' . $dbHost . ';dbname=' . $dbName, $dbUser, $dbPass,
                [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4'",
                    \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,
                ]
            );
        } catch (\PDOException $e) {
            echo '<h2>MySQL ERROR: ' . $e->getCode() . '</h2>';

            switch ($e->getCode()) {
                case 1045:
                    exit('Access credentials (username or password) to a database are incorrect');

                case 1049:
                    exit('The name of a database is specified incorrectly');

                case 2002:
                    exit('Invalid database server');
            }

            exit;
        }

        return $pdo;
    }
}
