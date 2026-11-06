<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Exceptions;

use RuntimeException;
use Throwable;

final class CollectionSchemaException extends RuntimeException
{
    /** @param array<string, string> $errors */
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null, public readonly array $errors = [])
    {
        parent::__construct($message, $code, $previous);
    }
}
