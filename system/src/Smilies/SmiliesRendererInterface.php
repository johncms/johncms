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
}
