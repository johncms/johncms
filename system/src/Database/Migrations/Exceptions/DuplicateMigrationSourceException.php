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

use RuntimeException;

/**
 * Two sources answering to one name.
 *
 * The name is what the journal records, so two directories behind it means one history describing
 * both — and a rollback of that source walking through migrations of whoever else claimed it. A
 * module whose alias is "system" would be asking the CMS to roll back its own schema.
 *
 * Fatal, and fatal early: this is raised while the sources are being collected, before a single
 * migration is read. It is only ever reached from the migration commands and from installing a
 * module, never from serving a page.
 */
final class DuplicateMigrationSourceException extends RuntimeException
{
}
