<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Database\Schema;

/**
 * A key over one or more columns. A dropped key carries its name and no columns.
 */
final readonly class IndexDefinition
{
    /**
     * @param list<string> $columns
     */
    public function __construct(
        public IndexType $type,
        public array $columns = [],
        public ?string $name = null,
    ) {
    }
}
