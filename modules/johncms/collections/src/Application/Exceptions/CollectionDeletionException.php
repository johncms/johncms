<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Exceptions;

use RuntimeException;

final class CollectionDeletionException extends RuntimeException
{
    /** @param array<string, list<string>> $errors */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('Collection deletion could not be completed.');
    }
}
