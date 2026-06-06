<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Exceptions;

use Johncms\Exceptions\ValidationException;

final class AlbumAccessDeniedException extends ValidationException
{
    public function __construct(public readonly int $ownerId)
    {
        parent::__construct('Access denied');
    }
}
