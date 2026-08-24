<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Exceptions;

final class ForumAccessDeniedException extends ForumException
{
    public function __construct(
        ForumErrorCode|string $errorCode = ForumErrorCode::FORUM_ACCESS_DENIED,
        ?string $message = null,
    ) {
        if (is_string($errorCode)) {
            parent::__construct(ForumErrorCode::FORUM_ACCESS_DENIED, $errorCode);
            return;
        }

        parent::__construct($errorCode, $message);
    }
}
