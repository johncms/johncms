<?php

declare(strict_types=1);

namespace Johncms\Comments\Application\Exceptions;

use RuntimeException;

/** The text cannot be saved; the message is meant for the author. */
final class CommentValidationException extends RuntimeException
{
}
