<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Database\Schema\Adapters;

/**
 * The few things a table description has to know about the database it is being written to.
 *
 * A migration describes a table once and the same file runs everywhere, so what differs between
 * databases is collected here rather than spread over the compiler: the word index MySQL can
 * write and SQLite cannot, and the scope an index name lives in.
 *
 * The defaults are those of MySQL, the database a site runs on.
 */
final readonly class SchemaPlatform
{
    /** The databases whose schema builder can write a word index. */
    private const array DRIVERS_WITH_FULL_TEXT = ['mysql', 'mariadb'];

    /** The databases where an index name has to be unique across the whole of it, not per table. */
    private const array DRIVERS_WITH_GLOBAL_INDEX_NAMES = ['sqlite', 'pgsql'];

    public function __construct(
        public bool $supportsFullText = true,
        public bool $indexNamesAreGlobal = false,
    ) {
    }

    public static function fromDriver(string $driver): self
    {
        return new self(
            supportsFullText: in_array($driver, self::DRIVERS_WITH_FULL_TEXT, true),
            indexNamesAreGlobal: in_array($driver, self::DRIVERS_WITH_GLOBAL_INDEX_NAMES, true),
        );
    }

    /**
     * The name an index is created under.
     *
     * The names come from 9.x, where a table names its key after the column it covers: a dozen
     * tables have an index called `user_id`. MySQL keeps those names per table and takes them as
     * they are; where they share one namespace the table has to be part of the name, or the
     * second migration to run collides with the first.
     *
     * A name the schema builder made up itself already begins with the table, and is left alone.
     */
    public function indexName(string $table, ?string $name): ?string
    {
        if ($name === null || ! $this->indexNamesAreGlobal || str_starts_with($name, $table . '_')) {
            return $name;
        }

        return $table . '_' . $name;
    }
}
