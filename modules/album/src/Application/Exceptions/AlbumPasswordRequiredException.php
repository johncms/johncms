<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Exceptions;

use Johncms\Exceptions\ValidationException;

final class AlbumPasswordRequiredException extends ValidationException
{
    public function __construct(
        public readonly int $albumId,
        public readonly int $ownerId,
        public readonly bool $incorrectPassword,
    ) {
        parent::__construct('Password required');
    }
}
