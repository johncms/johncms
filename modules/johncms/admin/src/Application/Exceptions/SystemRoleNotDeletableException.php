<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Exceptions;

use RuntimeException;

/**
 * A built-in role cannot be deleted: the code refers to it by slug, and an installation without
 * it would have no guest role, no default role, or no way back into the admin panel.
 */
final class SystemRoleNotDeletableException extends RuntimeException
{
}
