<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Exceptions;

use Johncms\Exceptions\ValidationException;

final class AlbumPhotoFileMissingException extends ValidationException
{
    protected $message = 'File does not exist';
}
