<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Exceptions;

abstract class ForumException extends \RuntimeException
{
    public function __construct(
        private readonly ForumErrorCode $errorCode,
        ?string $message = null,
    ) {
        parent::__construct($message ?? $errorCode->message());
    }

    public function getErrorCode(): ForumErrorCode
    {
        return $this->errorCode;
    }
}
