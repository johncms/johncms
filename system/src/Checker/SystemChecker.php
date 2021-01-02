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

    public function checkExtensions(): array
    {
        return [
            [
                'name'        => d__('system', 'PHP version'),
                'value'       => PHP_VERSION,
                'error'       => (PHP_VERSION_ID < 70300),
                'error_level' => self::CRITICAL,
                'description' => d__('system', 'The PHP version must be at least %s', '7.3'),
            ],
            [
                'name'        => 'PDO',
                'value'       => class_exists(PDO::class) ? d__('system', 'Yes') : d__('system', 'No'),
                'error'       => ! class_exists(PDO::class),
                'error_level' => self::CRITICAL,
                'description' => d__('system', 'PHP extension PDO must be installed'),
            ],
            [
                'name'        => d__('system', 'Imagick or GD extension'),
                'value'       => (extension_loaded('gd') || extension_loaded('imagick')) ? d__('system', 'Yes') : d__('system', 'No'),
                'error'       => (! extension_loaded('gd') && ! extension_loaded('imagick')),
                'error_level' => self::CRITICAL,
                'description' => d__('system', 'You must install the php extension Imagick or GD'),
            ],
            [
                'name'        => d__('system', 'zlib extension'),
                'value'       => extension_loaded('zlib') ? d__('system', 'Yes') : d__('system', 'No'),
                'error'       => ! extension_loaded('zlib'),
                'error_level' => self::CRITICAL,
                'description' => d__('system', 'PHP extension zlib must be installed'),
            ],
            [
                'name'        => d__('system', 'mbstring extension'),
                'value'       => extension_loaded('mbstring') ? d__('system', 'Yes') : d__('system', 'No'),
                'error'       => ! extension_loaded('mbstring'),
                'error_level' => self::CRITICAL,
                'description' => d__('system', 'PHP extension mbstring must be installed'),
            ],
            [
                'name'        => d__('system', 'fileinfo extension'),
                'value'       => extension_loaded('fileinfo') ? d__('system', 'Yes') : d__('system', 'No'),
                'error'       => ! extension_loaded('fileinfo'),
                'error_level' => self::CRITICAL,
                'description' => d__('system', 'PHP extension fileinfo must be installed'),
            ],
        ];
    }
}
