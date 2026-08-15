<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Exceptions;

use RuntimeException;

/**
 * The slug of a new role is already in use. Checks refer to roles by slug, so two of them may
 * never share one.
 */
final class RoleSlugTakenException extends RuntimeException
{
}
