<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Exceptions;

use RuntimeException;

/** The caller must retry its entire operation, including any outer transaction. */
final class CollectionWriteConflictException extends RuntimeException
{
}
