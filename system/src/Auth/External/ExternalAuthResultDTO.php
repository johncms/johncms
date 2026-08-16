<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\External;

final readonly class ExternalAuthResultDTO
{
    public function __construct(
        public ExternalAuthStatus $status,
        public ?int $userId = null,
    ) {
    }
}
