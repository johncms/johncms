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
 * How much markup a piece of content is allowed to carry.
 *
 * The policy is what the calling code chooses; which library enforces it, and how, is the
 * business of the sanitizer alone.
 */
enum HtmlPolicy
{
    /**
     * Content written in the editor: posts, comments, articles. Block elements, images,
     * tables, embedded media and a fixed list of CSS classes are allowed.
     */
    case RichContent;

    /**
     * Inline formatting and links only, no block elements. For a short text shown inside a
     * label or a sentence, where a paragraph would break the layout around it.
     */
    case Inline;

    /**
     * Inline formatting plus paragraphs, for a short text that stands on its own.
     */
    case InlineWithParagraphs;
}
