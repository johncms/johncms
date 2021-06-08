<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Exceptions;

use RuntimeException;

class ValidationException extends RuntimeException
{
    /** @var array */
    protected $errors = [];

    public static function withErrors(array $errors): self
    {
        $exception = new self('Validation error');
        $exception->setErrors($errors);
        return $exception;
    }

    private function setErrors(array $errors): void
    {
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
