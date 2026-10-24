<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Exceptions;

use RuntimeException;

/**
 * A form of the account was not accepted: what is wrong, field by field.
 */
final class UserEditException extends RuntimeException
{
    /**
     * @param array<string, list<string>> $errors
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('The form of the account is not valid.');
    }
}
