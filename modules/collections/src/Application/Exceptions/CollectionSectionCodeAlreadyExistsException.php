<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Exceptions;

use RuntimeException;

/**
 * Thrown when saving a section whose code is already used by another section
 * under the same parent.
 */
final class CollectionSectionCodeAlreadyExistsException extends RuntimeException
{
}
