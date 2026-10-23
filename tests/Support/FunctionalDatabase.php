<?php

declare(strict_types=1);

namespace Tests\Support;

use LogicException;
use PDO;

/**
 * The database the functional suite runs against.
 *
 * SQLite in memory unless TEST_DATABASE_URL names a MySQL or MariaDB database:
 *
 *     TEST_DATABASE_URL=mysql://root:password@mariadb:3306/johncms_test
 *
 * The suite drops that database and creates it anew before building the schema, so its name has
 * to end in _test: a URL copied from the configuration of a site would otherwise wipe the site.
 */
final readonly class FunctionalDatabase
{
    public const string VARIABLE = 'TEST_DATABASE_URL';

    /**
     * @param array<string, string|int> $config The "pdo" section of the configuration.
     */
    private function __construct(private array $config)
    {
    }

    public static function fromEnvironment(): self
    {
        $url = getenv(self::VARIABLE);

        if ($url === false || $url === '') {
            return new self(['db_driver' => 'sqlite', 'db_name' => ':memory:']);
        }

        $parts = parse_url($url);

        if ($parts === false || ($parts['scheme'] ?? null) !== 'mysql') {
            throw new LogicException(sprintf(
                '%s has to look like mysql://user:password@host:3306/name_test, "%s" given.',
                self::VARIABLE,
                $url
            ));
        }

        $name = ltrim($parts['path'] ?? '', '/');

        if (preg_match('/^\w+_test$/', $name) !== 1) {
            throw new LogicException(sprintf(
                '%s names the database "%s". The suite drops the database it runs in, so the name has to end in _test.',
                self::VARIABLE,
                $name
            ));
        }

        return new self([
            'db_driver' => 'mysql',
            'db_host'   => $parts['host'] ?? '127.0.0.1',
            'db_port'   => $parts['port'] ?? 3306,
            'db_name'   => $name,
            'db_user'   => urldecode($parts['user'] ?? 'root'),
            'db_pass'   => urldecode($parts['pass'] ?? ''),
        ]);
    }

    public function isMysql(): bool
    {
        return $this->config['db_driver'] === 'mysql';
    }

    /**
     * @return array<string, string|int>
     */
    public function config(): array
    {
        return $this->config;
    }

    /**
     * Leaves an empty database for the migrations to build the schema in. SQLite in memory is
     * empty by itself.
     */
    public function prepare(): void
    {
        if (! $this->isMysql()) {
            return;
        }

        $server = new PDO(
            sprintf('mysql:host=%s;port=%d', $this->config['db_host'], $this->config['db_port']),
            (string) $this->config['db_user'],
            (string) $this->config['db_pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $server->exec(sprintf('DROP DATABASE IF EXISTS `%s`', $this->config['db_name']));
        $server->exec(sprintf(
            'CREATE DATABASE `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            $this->config['db_name']
        ));
    }
}
