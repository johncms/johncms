<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Utils;

/**
 * Converts plain user text into a safe HTML fragment.
 *
 * Use it only when the result is printed unescaped, e.g. because line breaks
 * have to be preserved. Plain text fields must be escaped in the template instead.
 */
final class PlainTextFormatter
{
    public static function toHtml(string $text): string
    {
        return nl2br(self::escape($text));
    }

    /**
     * Escapes plain text for embedding into an HTML fragment that is built in PHP.
     * Templates must use the view escaper instead.
     */
    public static function escape(string $text): string
    {
        return htmlspecialchars(trim($text), ENT_QUOTES, 'UTF-8');
    }
}
