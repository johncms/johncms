<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Exceptions;

enum DownloadsErrorCode: string
{
    case DOWNLOADS_CLOSED = 'DOWNLOADS_CLOSED';
    case DOWNLOADS_AUTH_REQUIRED = 'DOWNLOADS_AUTH_REQUIRED';

    public function httpStatus(): int
    {
        return 403;
    }

    public function title(): string
    {
        return __('Downloads');
    }

    public function message(): string
    {
        return match ($this) {
            self::DOWNLOADS_CLOSED        => __('Downloads are closed'),
            self::DOWNLOADS_AUTH_REQUIRED => __('For registered users only'),
        };
    }
}
