<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Exceptions;

use RuntimeException;

/**
 * Thrown when saving a field whose code is already used by another field of the
 * same collection.
 */
final class CollectionFieldCodeAlreadyExistsException extends RuntimeException
{
}
