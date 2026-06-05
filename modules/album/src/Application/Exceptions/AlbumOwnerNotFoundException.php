<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Exceptions;

use Johncms\Exceptions\ValidationException;

final class AlbumOwnerNotFoundException extends ValidationException
{
    protected $message = 'User does not exists';
}
