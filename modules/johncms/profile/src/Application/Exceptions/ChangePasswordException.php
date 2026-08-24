<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Exceptions;

use Johncms\Exceptions\ValidationException;

final class ChangePasswordException extends ValidationException
{
    /**
     * @param list<string> $errors
     */
    public function __construct(array $errors)
    {
        parent::__construct('Validation error');
        $this->errors = $errors;
    }
}
