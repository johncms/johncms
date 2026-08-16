<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Security;

/**
 * Takes everything unsafe out of a piece of HTML.
 *
 * The caller states what kind of content it has (see HtmlPolicy) and gets back markup it may
 * print as it is. Which library does the work is deliberately invisible here: it is an
 * implementation detail that must be replaceable without touching a single caller.
 */
interface HtmlSanitizerInterface
{
    /**
     * Sanitized markup, safe to print without escaping.
     */
    public function sanitize(string $html, HtmlPolicy $policy = HtmlPolicy::RichContent): string;

    /**
     * The same content as plain text: sanitized, stripped of its tags, with the entities
     * decoded and the whitespace collapsed. For previews, meta descriptions and page titles.
     */
    public function toPlainText(string $html, HtmlPolicy $policy = HtmlPolicy::RichContent): string;
}
