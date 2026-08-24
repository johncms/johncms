<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Exceptions;

use RuntimeException;

/**
 * No role with that id — deleted while the page was open, or an address typed by hand.
 */
final class RoleNotFoundException extends RuntimeException
{
}
