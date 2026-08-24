<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Exceptions;

use Johncms\Exceptions\ValidationException;

final class ContactNotFoundException extends ValidationException
{
    protected $message = 'Contact not found';
}
