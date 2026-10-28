<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Exceptions;

use RuntimeException;

/**
 * A section of the forum was not deleted: what it holds was sent nowhere it can go, or deleting it
 * with its topics was asked for by someone who may not.
 */
final class SectionDeletionException extends RuntimeException
{
}
