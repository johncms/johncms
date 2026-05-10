<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Exceptions;

abstract class DownloadsException extends \RuntimeException
{
    public function __construct(
        private readonly DownloadsErrorCode $errorCode,
        ?string $message = null,
    ) {
        parent::__construct($message ?? $errorCode->message());
    }

    public function getErrorCode(): DownloadsErrorCode
    {
        return $this->errorCode;
    }
}
