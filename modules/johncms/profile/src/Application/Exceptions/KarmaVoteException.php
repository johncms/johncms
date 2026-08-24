<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Exceptions;

use RuntimeException;

final class KarmaVoteException extends RuntimeException
{
    /**
     * @param list<string> $errors
     */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('Karma vote is not allowed');
    }

    /**
     * @return list<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
