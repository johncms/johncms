<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\External\Providers;

use Johncms\Auth\External\AbstractOAuth2Provider;
use Johncms\Auth\External\ExternalIdentityDTO;

final class GoogleProvider extends AbstractOAuth2Provider
{
    public function key(): string
    {
        return 'google';
    }

    public function label(): string
    {
        return 'Google';
    }

    protected function authorizeUrl(): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth';
    }

    protected function tokenUrl(): string
    {
        return 'https://oauth2.googleapis.com/token';
    }

    protected function userInfoUrl(): string
    {
        return 'https://openidconnect.googleapis.com/v1/userinfo';
    }

    protected function scope(): string
    {
        return 'openid email profile';
    }

    protected function mapIdentity(array $userInfo, array $token): ExternalIdentityDTO
    {
        return new ExternalIdentityDTO(
            providerUserId: (string) ($userInfo['sub'] ?? ''),
            email: isset($userInfo['email']) ? (string) $userInfo['email'] : null,
            // Google says so explicitly, and an unverified address there is common enough that
            // assuming otherwise would be an account takeover waiting to happen.
            emailVerified: (bool) ($userInfo['email_verified'] ?? false),
            nickname: isset($userInfo['name']) ? (string) $userInfo['name'] : null,
            avatarUrl: isset($userInfo['picture']) ? (string) $userInfo['picture'] : null,
        );
    }
}
