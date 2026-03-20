<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Exceptions;

final class UploadException extends ForumValidationException
{
    /**
     * @param array<int, string> $errors
     */
    public function __construct(private array $errors)
    {
        parent::__construct(ForumErrorCode::FORUM_UPLOAD_FAILED);
    }

    /**
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
