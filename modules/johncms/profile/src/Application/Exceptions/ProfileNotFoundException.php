<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Exceptions;

use Johncms\Exceptions\ValidationException;

final class ProfileNotFoundException extends ValidationException
{
    protected $message = 'This User does not exists';
}
