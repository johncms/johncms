<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Exceptions;

use RuntimeException;

/**
 * Thrown when saving an item whose code is already used by another item in the
 * same collection and section.
 */
final class CollectionItemCodeAlreadyExistsException extends RuntimeException
{
}
