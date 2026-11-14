<?php

declare(strict_types=1);

namespace Johncms\Comments\Application\Exceptions;

use RuntimeException;

/** The discussed object does not exist or the visitor may not see it. */
final class CommentSubjectNotFoundException extends RuntimeException
{
}
