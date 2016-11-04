<?php

namespace Johncms;

use Interop\Container\ContainerInterface;

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
