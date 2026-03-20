<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Exceptions;

final class UploadExpiredException extends ForumValidationException
{
    public function __construct(
        private int $topicId,
        private int $page,
    ) {
        parent::__construct(ForumErrorCode::FORUM_UPLOAD_EXPIRED);
    }

    public function getTopicId(): int
    {
        return $this->topicId;
    }

    public function getPage(): int
    {
        return $this->page;
    }
}
