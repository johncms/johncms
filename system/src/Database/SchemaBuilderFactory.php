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
use Psr\Container\ContainerInterface;

/**
 * The schema builder, for the few places that create or alter tables.
 *
 * It is taken from the capsule service, so asking for it boots Eloquent first: Capsule::schema()
 * answers with a connection of null until the capsule has been set as global.
 */
final class SchemaBuilderFactory
{
    public function __invoke(ContainerInterface $container): Builder
    {
        return $container->get(Capsule::class)->schema();
    }
}
