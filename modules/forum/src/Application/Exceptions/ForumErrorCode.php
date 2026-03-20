<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Exceptions;

enum ForumErrorCode: string
{
    case FORUM_AUTH_REQUIRED = 'FORUM_AUTH_REQUIRED';
    case FORUM_CLOSED = 'FORUM_CLOSED';
    case FORUM_ACCESS_DENIED = 'FORUM_ACCESS_DENIED';
    case FORUM_NOT_FOUND = 'FORUM_NOT_FOUND';
    case FORUM_WRONG_DATA = 'FORUM_WRONG_DATA';
    case FORUM_SEARCH_INVALID_LENGTH = 'FORUM_SEARCH_INVALID_LENGTH';
    case FORUM_UPLOAD_FAILED = 'FORUM_UPLOAD_FAILED';
    case FORUM_UPLOAD_EXPIRED = 'FORUM_UPLOAD_EXPIRED';

    public function httpStatus(): int
    {
        return match ($this) {
            self::FORUM_AUTH_REQUIRED,
            self::FORUM_CLOSED,
            self::FORUM_ACCESS_DENIED => 403,
            self::FORUM_NOT_FOUND => 404,
            self::FORUM_WRONG_DATA,
            self::FORUM_SEARCH_INVALID_LENGTH,
            self::FORUM_UPLOAD_FAILED => 422,
            self::FORUM_UPLOAD_EXPIRED => 408,
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::FORUM_AUTH_REQUIRED,
            self::FORUM_CLOSED => __('Forum'),
            self::FORUM_ACCESS_DENIED => __('Access forbidden'),
            self::FORUM_NOT_FOUND,
            self::FORUM_WRONG_DATA,
            self::FORUM_SEARCH_INVALID_LENGTH,
            self::FORUM_UPLOAD_FAILED,
            self::FORUM_UPLOAD_EXPIRED => __('Wrong data'),
        };
    }

    public function message(): string
    {
        return match ($this) {
            self::FORUM_AUTH_REQUIRED => __('For registered users only'),
            self::FORUM_CLOSED => __('Forum is closed'),
            self::FORUM_ACCESS_DENIED => __('Access forbidden'),
            self::FORUM_NOT_FOUND => __('Wrong data'),
            self::FORUM_WRONG_DATA => __('Wrong data'),
            self::FORUM_SEARCH_INVALID_LENGTH => __('Search query must be between 2 and 64 characters'),
            self::FORUM_UPLOAD_FAILED => __('Error uploading file'),
            self::FORUM_UPLOAD_EXPIRED => __('The time allotted for the file upload has expired'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::FORUM_AUTH_REQUIRED => 'Forum is available for authenticated users only.',
            self::FORUM_CLOSED => 'Forum is closed by configuration.',
            self::FORUM_ACCESS_DENIED => 'User has no permission for this action.',
            self::FORUM_NOT_FOUND => 'Requested forum entity was not found.',
            self::FORUM_WRONG_DATA => 'Input or context data is invalid for operation.',
            self::FORUM_SEARCH_INVALID_LENGTH => 'Search query length is outside allowed limits.',
            self::FORUM_UPLOAD_FAILED => 'File upload failed due to validation or storage errors.',
            self::FORUM_UPLOAD_EXPIRED => 'Upload time window has expired.',
        };
    }
}
