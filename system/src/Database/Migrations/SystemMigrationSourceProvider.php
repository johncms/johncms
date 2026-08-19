<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Database\Migrations;

/**
 * The migrations of the core, which run before every other source: a module may point at the
 * tables of the core, and the core points at nobody's.
 */
final readonly class SystemMigrationSourceProvider implements MigrationSourceProviderInterface
{
    public const string NAME = 'system';

    public function __construct(private string $directory = ROOT_PATH . 'system/migrations')
    {
    }

    public function sources(): array
    {
        return [new MigrationSource(self::NAME, $this->directory)];
    }
}
