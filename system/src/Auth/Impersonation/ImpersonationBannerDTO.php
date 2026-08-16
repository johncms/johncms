<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Impersonation;

/**
 * What the banner on every page says while an administrator browses as somebody else.
 */
final readonly class ImpersonationBannerDTO
{
    public function __construct(
        public int $userId,
        public string $userName,
        public string $stopUrl = '/impersonation/stop',
    ) {
    }
}
