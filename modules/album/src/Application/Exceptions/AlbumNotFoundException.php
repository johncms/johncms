<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Exceptions;

use Johncms\Exceptions\ValidationException;

final class AlbumNotFoundException extends ValidationException
{
    protected $message = 'Wrong data';
}
