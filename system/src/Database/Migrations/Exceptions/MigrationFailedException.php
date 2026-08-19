<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Database\Migrations\Exceptions;

use Johncms\Database\Migrations\MigrationDirection;
use Johncms\Database\Migrations\MigrationFile;
use RuntimeException;
use Throwable;

/**
 * A migration that threw. The run stops here and the migration is not written into the journal,
 * so running again starts from it — but on a database whose DDL is not transactional it may have
 * carried out part of its work already, which is why the message says so out loud.
 */
final class MigrationFailedException extends RuntimeException
{
    public function __construct(
        public readonly MigrationFile $migration,
        public readonly MigrationDirection $direction,
        Throwable $previous,
    ) {
        parent::__construct(
            sprintf(
                'The migration %s of %s failed to run %s: %s. It was not recorded as applied, but part of its work may already have been carried out.',
                $migration->name,
                $migration->source,
                $direction->value,
                $previous->getMessage()
            ),
            0,
            $previous
        );
    }
}
