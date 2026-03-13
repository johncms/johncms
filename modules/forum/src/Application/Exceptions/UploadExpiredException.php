<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Exceptions;

final class UploadExpiredException extends \RuntimeException
{
    public function __construct(
        private int $topicId,
        private int $page,
    ) {
        parent::__construct('The time allotted for the file upload has expired.');
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
