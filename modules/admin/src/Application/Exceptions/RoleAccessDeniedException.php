<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Exceptions;

use RuntimeException;

/**
 * The visitor may not manage roles at all, or not this one: a role standing above their own is
 * out of reach, otherwise the editor would be a way up the hierarchy.
 */
final class RoleAccessDeniedException extends RuntimeException
{
}
