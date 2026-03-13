<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Exceptions;

final class UploadException extends \RuntimeException
{
    /**
     * @param array<int, string> $errors
     */
    public function __construct(private array $errors)
    {
        parent::__construct('File upload failed.');
    }

    /**
     * @return array<int, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
