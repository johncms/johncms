<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Exceptions;

use Twig\Markup;

final class UploadException extends ForumValidationException
{
    /**
     * @param array<int, string|Markup> $errors
     */
    public function __construct(private array $errors)
    {
        parent::__construct(ForumErrorCode::FORUM_UPLOAD_FAILED);
    }

    /**
     * @return array<int, string|Markup>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
