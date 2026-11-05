<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Exceptions;

use RuntimeException;

final class CollectionItemFormException extends RuntimeException
{
    /** @param array<string, list<string>> $errors */
    public function __construct(public readonly array $errors, ?\Throwable $previous = null)
    {
        parent::__construct('The collection item is not valid.', 0, $previous);
    }
}
