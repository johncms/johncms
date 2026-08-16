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

final class GithubProvider extends AbstractOAuth2Provider
{
    public function key(): string
    {
        return 'github';
    }

    public function label(): string
    {
        return 'GitHub';
    }

    protected function authorizeUrl(): string
    {
        return 'https://github.com/login/oauth/authorize';
    }

    protected function tokenUrl(): string
    {
        return 'https://github.com/login/oauth/access_token';
    }

    protected function userInfoUrl(): string
    {
        return 'https://api.github.com/user';
    }

    protected function scope(): string
    {
        return 'read:user user:email';
    }

    protected function mapIdentity(array $userInfo, array $token): ExternalIdentityDTO
    {
        return new ExternalIdentityDTO(
            providerUserId: (string) ($userInfo['id'] ?? ''),
            email: isset($userInfo['email']) ? (string) $userInfo['email'] : null,
            // The primary address of a GitHub account is confirmed by GitHub itself; the profile
            // endpoint only ever returns that one.
            emailVerified: isset($userInfo['email']),
            nickname: isset($userInfo['login']) ? (string) $userInfo['login'] : null,
            avatarUrl: isset($userInfo['avatar_url']) ? (string) $userInfo['avatar_url'] : null,
        );
    }
}
