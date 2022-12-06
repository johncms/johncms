<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users\AuthProviders;

use Johncms\Users\User;

interface AuthProviderInterface
{
    /**
     * This method should check stored data and authenticate user if possible.
     */
    public function authenticate(): ?User;

    /**
     * This method should save the user's data to work in the authentication method
     */
    public function store(User $user): void;

    /**
     * This method should delete stored data
     */
    public function forget(): void;
}
