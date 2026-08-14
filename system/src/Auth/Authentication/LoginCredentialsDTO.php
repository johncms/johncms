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

final readonly class LoginCredentialsDTO
{
    /**
     * @param string $captchaAnswer The verification code, when the form asked for one. Empty
     *                              means it was not asked for or not filled in.
     */
    public function __construct(
        public string $login,
        public string $password,
        public string $captchaAnswer = '',
    ) {
    }
}
