<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Exceptions;

use RuntimeException;

/**
 * An advertising link cannot be turned on: its hits or days are used up, and the site would hide
 * it again on the next page.
 */
final class AdLimitReachedException extends RuntimeException
{
}
