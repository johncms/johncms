<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Exceptions;

use RuntimeException;

/**
 * The form of an article was not accepted: what is wrong, field by field.
 */
final class ArticleFormException extends RuntimeException
{
    /**
     * @param array<string, list<string>> $errors
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('The form of the article is not valid.');
    }
}
