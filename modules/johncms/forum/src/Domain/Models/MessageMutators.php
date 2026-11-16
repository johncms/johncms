<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Models;

/**
 * Typed values of the nullable columns of a post.
 */
trait MessageMutators
{
    /**
     * These columns are nullable in the database, and a template that reads a null attribute of a
     * model falls through to a method of the same name. The accessors keep them typed instead.
     */
    public function getDeletedAttribute(mixed $value): bool
    {
        return (bool) $value;
    }

    public function getPinnedAttribute(mixed $value): bool
    {
        return (bool) $value;
    }

    public function getDeletedByAttribute(mixed $value): string
    {
        return (string) $value;
    }

    public function getEditorNameAttribute(mixed $value): string
    {
        return (string) $value;
    }

    public function getEditCountAttribute(mixed $value): int
    {
        return (int) $value;
    }

    public function getUserNameAttribute(mixed $value): string
    {
        return (string) $value;
    }
}
