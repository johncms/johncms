<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Exceptions;

use RuntimeException;

/**
 * The settings of the news were not accepted: what is wrong, field by field.
 */
final class NewsSettingsException extends RuntimeException
{
    /**
     * @param array<string, list<string>> $errors
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('The settings of the news are not valid.');
    }
}
