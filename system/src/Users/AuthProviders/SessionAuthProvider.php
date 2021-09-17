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

use Johncms\Http\Session;
use Johncms\Users\User;

class SessionAuthProvider implements AuthProviderInterface
{
    public const AUTH_SESSION_ID = '_johncms_auth';

    public Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function authenticate(): ?User
    {
        $sessionData = $this->session->get(self::AUTH_SESSION_ID);
        if (empty($sessionData['user_id']) || empty($sessionData['user_password'])) {
            return null;
        }

        /** @var User|null $user */
        $user = (new User())->find($sessionData['user_id']);
        if ($user === null || $user->password !== $sessionData['user_password']) {
            $this->forget();
            return null;
        }

        return $user;
    }

    public function store(User $user): void
    {
        $this->session->set(
            self::AUTH_SESSION_ID,
            [
                'user_id'       => $user->id,
                'user_password' => $user->password,
            ]
        );
    }

    public function forget(): void
    {
        $this->session->remove(self::AUTH_SESSION_ID);
    }
}
