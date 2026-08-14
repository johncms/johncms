<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Authentication;

final readonly class LoginResultDTO
{
    /**
     * @param int|null $userId     The account the credentials belong to. Present whenever they
     *                             were correct, including when something else stops the sign-in —
     *                             the admin screen needs it to say whose account is awaiting
     *                             approval.
     * @param int      $retryAfter Seconds to wait, set only when the attempt was refused for
     *                             being one of too many.
     */
    public function __construct(
        public LoginStatus $status,
        public ?int $userId = null,
        public int $retryAfter = 0,
    ) {
    }

    public function isSuccessful(): bool
    {
        return $this->status === LoginStatus::Success;
    }
}
