<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Exceptions;

use Johncms\Exceptions\ValidationException;

final class VoteNotAllowedException extends ValidationException
{
    protected $message = 'You cannot vote for this photo.';
}
