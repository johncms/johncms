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

use PDO;

class SystemChecker
{
    public const CRITICAL = 1;
    public const WARNING = 2;
    public const INFO = 3;
    public const MIN_PHP_VERSION = '7.3';
    public const MIN_PHP_VERSION_ID = 70300;

    public function checkExtensions(): array
    {
        return [
            [
                'name'        => d__('system', 'PHP version'),
                'check_code'  => 'php',
                'value'       => PHP_VERSION,
                'error'       => (PHP_VERSION_ID < self::MIN_PHP_VERSION_ID),
                'error_level' => self::CRITICAL,
                'description' => d__('system', 'The PHP version must be at least %s', self::MIN_PHP_VERSION),
            ],
            [
                'name'        => 'PDO',
                'check_code'  => 'pdo',
                'value'       => class_exists(PDO::class) ? d__('system', 'Yes') : d__('system', 'No'),
                'error'       => ! class_exists(PDO::class),
                'error_level' => self::CRITICAL,
                'description' => d__('system', 'PHP extension PDO must be installed'),
            ],
            [
                'name'        => d__('system', 'Imagick or GD extension'),
                'check_code'  => 'imagick',
                'value'       => (extension_loaded('gd') || extension_loaded('imagick')) ? d__('system', 'Yes') : d__('system', 'No'),
                'error'       => (! extension_loaded('gd') && ! extension_loaded('imagick')),
                'error_level' => self::CRITICAL,
                'description' => d__('system', 'You must install the php extension Imagick or GD'),
            ],
            [
                'name'        => d__('system', 'zlib extension'),
                'check_code'  => 'zlib',
                'value'       => extension_loaded('zlib') ? d__('system', 'Yes') : d__('system', 'No'),
                'error'       => ! extension_loaded('zlib'),
                'error_level' => self::CRITICAL,
                'description' => d__('system', 'PHP extension zlib must be installed'),
            ],
            [
                'name'        => d__('system', 'mbstring extension'),
                'check_code'  => 'mbstring',
                'value'       => extension_loaded('mbstring') ? d__('system', 'Yes') : d__('system', 'No'),
                'error'       => ! extension_loaded('mbstring'),
                'error_level' => self::CRITICAL,
                'description' => d__('system', 'PHP extension mbstring must be installed'),
            ],
            [
                'name'        => d__('system', 'fileinfo extension'),
                'check_code'  => 'fileinfo',
                'value'       => extension_loaded('fileinfo') ? d__('system', 'Yes') : d__('system', 'No'),
                'error'       => ! extension_loaded('fileinfo'),
                'error_level' => self::CRITICAL,
                'description' => d__('system', 'PHP extension fileinfo must be installed'),
            ],
        ];
    }

    public function recommendations(): array
    {
        $opcache_enabled = (ini_get('opcache.enable') === '1');
        $opcache_loaded = extension_loaded('Zend OPcache');

        return [
            [
                'name'        => d__('system', 'opcache extension'),
                'check_code'  => 'opcache',
                'value'       => $opcache_loaded ? d__('system', 'Yes') : d__('system', 'No'),
                'error'       => ! $opcache_loaded,
                'error_level' => self::WARNING,
                'description' => d__('system', 'It is recommended to install the php opcache extension for better performance.'),
            ],
            [
                'name'        => 'opcache.enable=1',
                'check_code'  => 'opcache_enable',
                'value'       => $opcache_enabled ? d__('system', 'Yes') : d__('system', 'No'),
                'error'       => ! $opcache_enabled,
                'error_level' => self::WARNING,
                'description' => d__('system', 'It is recommended to enable the php opcache extension to improve performance.'),
            ],
        ];
    }

    public function checkDatabase(): array
    {
        $db_checker = new DBChecker();
        $version_info = $db_checker->versionInfo();
        $check_mysqlnd = $db_checker->checkMysqlnd();

        return [
            [
                'name'        => d__('system', 'Version of the database server'),
                'check_code'  => 'db_version',
                'value'       => $version_info['version_raw'],
                'error'       => $version_info['error'],
                'error_level' => self::CRITICAL,
                'description' => d__('system', 'The system requires a MySQL server version %s or higher or MariaDB %s or higher.', DBChecker::MYSQL_VERSION, DBChecker::MARIADB_VERSION),
            ],
            [
                'name'        => d__('system', 'mysqlnd test'),
                'check_code'  => 'mysqlnd',
                'value'       => $check_mysqlnd ? d__('system', 'Yes') : d__('system', 'No'),
                'error'       => ! $check_mysqlnd,
                'error_level' => self::CRITICAL,
                'description' => d__(
                    'system',
                    'We use strict data type checks when developing the system. The CMS will not work correctly if the driver for working with the database returns incorrect data types.
PHP should work with the default driver: <a href="https://www.php.net/manual/en/intro.mysqlnd.php" target="_blank">MySQL Native Driver (mysqlnd)</a>.'
                ),
            ],
        ];
    }
}
