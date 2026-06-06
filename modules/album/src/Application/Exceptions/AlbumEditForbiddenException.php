<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Exceptions;

use Johncms\Exceptions\ValidationException;

final class AlbumEditForbiddenException extends ValidationException
{
    protected $message = 'Access denied';
}
