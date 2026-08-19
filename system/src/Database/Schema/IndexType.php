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

enum IndexType: string
{
    case Index = 'index';

    case Unique = 'unique';

    case Primary = 'primary';

    /**
     * A word index, for the searches written as MATCH ... AGAINST. Only some databases have
     * one at all; on the others the adapter leaves it out — see SchemaInterface.
     */
    case FullText = 'fulltext';
}
