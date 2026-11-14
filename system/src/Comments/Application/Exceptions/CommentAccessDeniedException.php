<?php

declare(strict_types=1);

namespace Johncms\Comments\Application\Exceptions;

use RuntimeException;

/** The visitor may not do this; the message is meant for them. */
final class CommentAccessDeniedException extends RuntimeException
{
}
