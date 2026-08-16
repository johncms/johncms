<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Mail;

/**
 * The body of one message in both forms a mail client may ask for.
 */
final readonly class RenderedEmailDTO
{
    public function __construct(
        public string $html,
        public string $text,
    ) {
    }
}
