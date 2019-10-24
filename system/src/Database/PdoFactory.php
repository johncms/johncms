<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Database;

use PDO;
use Psr\Container\ContainerInterface;

class PdoFactory
{
    public function __invoke(ContainerInterface $container) : PDO
    {
        $config = $container->get('config')['pdo'] ?? [];

        $dbHost = $config['db_host'] ?? 'localhost';
        $dbUser = $config['db_user'] ?? 'root';
        $dbPass = $config['db_pass'] ?? '';
        $dbName = $config['db_name'] ?? 'johncms';

        try {
            $pdo = new \PDO('mysql:host=' . $dbHost . ';dbname=' . $dbName, $dbUser, $dbPass,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4'",
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,
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
