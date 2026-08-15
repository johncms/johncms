<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Database;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Builder;
use PDO;
use Psr\Container\ContainerInterface;

/**
 * The schema builder, for the few places that create or alter tables.
 *
 * Asking the container for PDO first is the whole reason this factory exists: building that
 * service is what boots the Eloquent capsule, and Capsule::schema() answers with a connection of
 * null until it has been. Under HTTP something has always built it before a schema command runs;
 * from the command line nothing had, which is why those commands failed with "connection() on
 * null" until they were given this.
 */
final class SchemaBuilderFactory
{
    public function __invoke(ContainerInterface $container): Builder
    {
        $container->get(PDO::class);

        return Capsule::schema();
    }
}
