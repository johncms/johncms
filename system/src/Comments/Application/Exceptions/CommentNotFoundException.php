<?php

declare(strict_types=1);

namespace Johncms\Comments\Application\Exceptions;

use RuntimeException;

/** The comment does not exist or does not belong to this discussion. */
final class CommentNotFoundException extends RuntimeException
{
}
