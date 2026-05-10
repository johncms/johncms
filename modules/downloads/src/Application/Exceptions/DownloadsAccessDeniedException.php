<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Exceptions;

final class DownloadsAccessDeniedException extends DownloadsException
{
    public function __construct(DownloadsErrorCode $errorCode, ?string $message = null)
    {
        parent::__construct($errorCode, $message);
    }
}
