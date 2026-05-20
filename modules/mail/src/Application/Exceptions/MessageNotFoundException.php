<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Exceptions;

use Johncms\Exceptions\ValidationException;

final class MessageNotFoundException extends ValidationException
{
    protected $message = 'Message does not exist';
}
