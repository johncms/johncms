<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Smilies;

interface SmiliesRendererInterface
{
    /**
     * Replaces smiley codes in the given text with their HTML representation.
     */
    public function render(string $text, bool $withAdminSmilies = false): string;

    /**
     * The replacement map itself: a smiley code to the HTML that draws it.
     *
     * For a caller that replaces over a structure rather than over a string — the content
     * pipeline walks the text nodes of a parsed post, so that a code written inside an
     * attribute or inside a block of code is left alone.
     *
     * @return array<string, string>
     */
    public function map(bool $withAdminSmilies = false): array;
}
