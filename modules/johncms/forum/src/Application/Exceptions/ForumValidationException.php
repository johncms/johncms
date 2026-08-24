<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Exceptions;

class ForumValidationException extends ForumException
{
    public function __construct(
        ForumErrorCode|string $errorCode = ForumErrorCode::FORUM_WRONG_DATA,
        ?string $message = null,
    ) {
        if (is_string($errorCode)) {
            parent::__construct(ForumErrorCode::FORUM_WRONG_DATA, $errorCode);
            return;
        }

        parent::__construct($errorCode, $message);
    }
}
