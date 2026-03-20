<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Exceptions;

final class ForumNotFoundException extends ForumException
{
    public function __construct(string $message)
    {
        parent::__construct(ForumErrorCode::FORUM_NOT_FOUND, $message);
    }
}
