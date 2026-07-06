<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Exceptions;

use RuntimeException;

/**
 * Thrown when a collection code collides with a reserved top-level URL segment,
 * which would make the collection unreachable.
 */
final class CollectionCodeReservedException extends RuntimeException
{
}
