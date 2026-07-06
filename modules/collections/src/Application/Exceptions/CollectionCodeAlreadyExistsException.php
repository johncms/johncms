<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Exceptions;

use RuntimeException;

/**
 * Thrown when saving a collection whose code is already used by another collection.
 */
final class CollectionCodeAlreadyExistsException extends RuntimeException
{
}
