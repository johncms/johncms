<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Exceptions;

use RuntimeException;

/**
 * The visitor may not change this account: no permission, or the account stands above them.
 */
final class UserEditForbiddenException extends RuntimeException
{
}
