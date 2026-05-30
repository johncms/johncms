<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Exceptions;

use Johncms\Exceptions\ValidationException;

final class ProfileAccessForbiddenException extends ValidationException
{
    protected $message = 'Access forbidden';
}
